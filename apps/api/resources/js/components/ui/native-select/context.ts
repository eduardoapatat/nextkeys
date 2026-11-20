import type { AcceptableValue } from 'reka-ui';
import type { InjectionKey, Ref } from 'vue';

/**
 * The value of the enclosing NativeSelect. Its options read it to render
 * `selected` themselves: Vue's SSR only marks the selected option of a
 * `v-model` select when the options are in the same template, not in a slot,
 * so without it the server HTML shows the first option until hydration.
 */
export const nativeSelectValueKey: InjectionKey<
    Readonly<Ref<AcceptableValue | AcceptableValue[] | undefined>>
> = Symbol('NativeSelectValue');

export function isSelectedValue(
    selected: AcceptableValue | AcceptableValue[] | undefined,
    value: unknown,
): boolean {
    if (value === undefined || selected === undefined) {
        return false;
    }

    const values = Array.isArray(selected) ? selected : [selected];

    return values.some((candidate) => String(candidate) === String(value));
}
