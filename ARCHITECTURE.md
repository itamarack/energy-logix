# Architecture Notes

## Formula Storage Strategy

Each `formula_versions` row stores:

- `expression` (`text`) — the main formula expression as a plain string (e.g. `BaseCommission * 1.1`)
- `variables` (`json`) — an array of `{name, expression}` objects for intermediate (derived) variables

Keeping everything in a single row avoids join complexity. A separate `intermediate_variables` table would require joining on every read, validation, and simulation call without offering meaningful benefit given the variable count typical in commission formulas. The JSON column is sufficient and maps naturally to the PHP array the evaluator consumes.

---

## Expression Evaluator (`app/Services/FormulaEvaluator.php`)

A custom **recursive-descent parser** — `eval()` is never used.

### Grammar

```
expression → term  (('+' | '-') term)*
term       → factor (('*' | '/') factor)*
factor     → NUMBER | IDENT | '(' expression ')'
```

### Three Phases

1. **Tokeniser** (`tokenise()`) — scans the raw string character-by-character and emits typed tokens: `NUMBER`, `IDENT`, `PLUS`, `MINUS`, `STAR`, `SLASH`, `LPAREN`, `RPAREN`. Raises `ParseException` on any unrecognised character.
2. **Parser** (`parseExpression` / `parseTerm` / `parseFactor`) — builds an AST from the token stream following the grammar above.
3. **Evaluator** (`evaluateNode()`) — walks the AST recursively, resolving `IDENT` nodes from the caller-supplied `$variables` map.

### Supported

- Operators: `+`, `-`, `*`, `/`
- Parentheses for precedence override
- Numeric literals (integer and float)
- PascalCase identifiers (`AnnualUsage`, `ContractValue`, etc.)

### Not Supported

- Bitwise, logical, or comparison operators
- String literals
- Exponentiation (`**`)
- Unary minus

### Exceptions

| Exception | Trigger |
|-----------|---------|
| `ParseException` | Unrecognised character, unexpected token, unclosed parenthesis |
| `UndefinedVariableException` | Identifier not present in the `$variables` map |
| `DivisionByZeroException` | Right-hand side of `/` evaluates to zero |

---

## Dependency Resolution (`app/Services/DependencyResolver.php`)

Intermediate variables may reference each other. The resolver determines the correct evaluation order using **Kahn's BFS topological sort**.

### Algorithm

**Phase 1 — Build the dependency graph:**
Tokenise each variable's expression and collect all `IDENT` tokens that name another intermediate variable (base inputs are excluded). For each such reference, add a directed edge `dependency → dependent` and increment the dependent's in-degree.

**Phase 2 — Kahn's BFS:**
Seed a queue with all zero-in-degree nodes. Repeatedly dequeue a node, append it to the sorted output, and decrement the in-degree of each of its dependents. Enqueue any dependent whose in-degree reaches zero.

**Phase 3 — Cycle check:**
If `count(sorted) < count(variables)` after BFS, the remaining nodes could not be processed — they form a cycle. `CircularDependencyException` is thrown listing the cycle members by name.

### Complexity

O(V + E) where V = number of intermediate variables, E = number of dependency edges.

### Why Kahn's BFS instead of DFS?

Kahn's BFS naturally produces the evaluation order as a by-product of the sort. DFS-based topological sort requires a separate post-order traversal pass plus explicit cycle detection bookkeeping. Kahn's makes both the sort and the cycle check a single linear pass.

---

## Validation Orchestration (`app/Services/FormulaValidator.php`)

`FormulaValidator::validate(string $expression, array $variables): void` runs a three-step pipeline:

1. **Intermediate syntax & variable check** — for each intermediate variable, call `FormulaEvaluator::validate($var['expression'], $allowedVars)` where `$allowedVars` = base inputs + all intermediate variable names. This catches syntax errors and undefined references in intermediates.
2. **Dependency resolution** — call `DependencyResolver::resolve($variables)`. If a cycle exists, `CircularDependencyException` propagates immediately.
3. **Main expression check** — call `FormulaEvaluator::validate($expression, $allowedVars)` where `$allowedVars` = base inputs + all intermediate variable names.

The validator has no side effects — it never reads from or writes to the database.

### Constants

- `BASE_INPUT_VARIABLES` — `['AnnualUsage', 'ContractValue', 'ContractLength', 'RiskScore']`
- `VARIABLE_COLUMN_MAP` — PascalCase → snake_case mapping used by the calculator and simulator to read contract columns

---

## Simulation vs. Calculation

### `CommissionCalculator`

Runs the full calculation pipeline (variable map → topological sort → evaluate intermediates → evaluate main expression) and **persists** a `CommissionCalculation` audit record via `CommissionCalculation::create()`. Returns the created model.

### `CommissionSimulator`

Mirrors the calculation logic in a private `calculateDryRun()` method that is identical to the calculator except it **skips `CommissionCalculation::create()`** entirely and returns only the numeric result. The public `simulate()` method:

1. Validates the target formula before touching any contract data.
2. Loads the currently active formula (if any) and all contracts.
3. Accumulates `$currentTotal` (active formula) and `$newTotal` (target formula) using `calculateDryRun()`.
4. Returns a `SimulationResult` DTO with `affected_contract_count`, `current_total_commission`, `new_total_commission`, and `difference`.

**Critical constraint:** `CommissionSimulator` must never call `CommissionCalculation::create()`. Simulation results are ephemeral.

---

## Queue / Async Note

Simulation runs **synchronously** in this implementation — it iterates all contracts in a single request. For production use with large contract datasets, a `SimulateFormulaJob` implementing `ShouldQueue` should be introduced. It would process contracts in chunks, accumulate totals via a cache or dedicated results table, and notify the caller on completion.

---

## Variable Naming Convention

Formula identifiers are **PascalCase** (`AnnualUsage`, `ContractValue`, `ContractLength`, `RiskScore`). These map 1:1 to the snake_case columns on the `contracts` table via the static lookup table in `FormulaValidator::VARIABLE_COLUMN_MAP`. The calculator and simulator use this mapping when building the variable map from a `Contract` model instance.

---

## Assumptions

- **No authentication** is required for this assessment. All API endpoints are publicly accessible.
- **Supported operators:** `+`, `-`, `*`, `/`. Exponentiation, bitwise, logical, and comparison operators are intentionally unsupported.
- **Contract field mapping:** the four base input variables map 1:1 to contract columns — no derived or computed contract fields are involved.
- **SQLite** is the default database for local development and tests.
