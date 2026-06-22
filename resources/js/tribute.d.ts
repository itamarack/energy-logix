declare module 'tributejs' {
    interface TributeOptions<T> {
        trigger?: string
        lookup?: keyof T | ((item: T, text: string) => string)
        fillAttr?: keyof T
        values?: T[] | ((text: string, cb: (items: T[]) => void) => void)
        menuItemTemplate?: (item: { original: T; index: number; score: number; string: string }) => string
        noMatchTemplate?: (() => string) | null
        selectTemplate?: (item: { original: T; index: number; score: number; string: string } | undefined) => string
        allowSpaces?: boolean
        autocompleteMode?: boolean
        spaceSelectsMatch?: boolean
        replaceTextSuffix?: string
        menuContainer?: HTMLElement
    }

    class Tribute<T = Record<string, unknown>> {
        constructor(options: TributeOptions<T>)
        attach(el: HTMLElement): void
        detach(el: HTMLElement): void
        isActive: boolean
    }

    export default Tribute
}
