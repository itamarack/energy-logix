Full Stack Developer Technical Assessment
Position: Senior Laravel + Vue Developer
Overview
Build a Dynamic Commission Engine that allows administrators to create and manage
commission formulas without changing application code.
The solution should demonstrate backend architecture, frontend design, problem-solving
ability, and scalability considerations.
Business Scenario
Energy brokers receive commission based on customer contracts.
Commission calculations must be configurable by administrators and support multiple
versions over time.
Example:
Version 1
Commission = Annual Usage × 0.05
Version 2
Commission = (Annual Usage × 0.05) + (Contract Length × 100)
The system should support creating, testing, and activating new commission formulas.
Functional Requirements
1. Formula Builder
Create a UI that allows administrators to create commission formulas.
Supported variables:

 Annual Usage
 Contract Value
 Contract Length
 Risk Score
Example formula:
(AnnualUsage * 0.05) + (ContractLength * 100)
Requirements:
 Save formulas in the database
 Validate formulas before saving
 Support multiple versions
 Only one version can be active at a time
2. Commission Calculation
Given a contract, calculate the commission using the active formula.
Display:
 Formula Version
 Input Values
 Calculation Result
Store calculation history for future reference.
3. Formula Impact Analysis
Before activating a new formula, provide a simulation mode.
The simulation should show:
 Number of affected contracts
 Current total commission
 New total commission
 Difference between the two
The simulation must not modify existing commission records.
4. Dependency Validation
Support calculated variables.
Example:

BaseCommission = AnnualUsage * 0.05
BonusCommission = BaseCommission * 0.10
FinalCommission = BaseCommission + BonusCommission
Requirements:
 Detect circular dependencies
 Prevent invalid formulas
 Calculate in the correct sequence
5. Audit Trail
Every commission calculation should be traceable.
Display:
 Formula Version
 Calculation Date
 Input Values
 Calculation Steps
 Final Result
Technical Requirements
Backend
 Laravel
 REST API
 Queue Jobs where appropriate
 Unit Tests
Frontend
 Vue
Database
 MySQL
Deliverables
 Source Code
 README
 Database Schema

 API Documentation
 Test Cases
 Architecture Notes
The focus is on maintainable architecture and clean implementation rather than UI
styling.