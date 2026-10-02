import { usePage } from '@inertiajs/react';

type Replacements = Record<string, string | number>;

export function useTranslation() {
    const { locale, locales, translations } = usePage().props;

    const t = (key?: string | null, replacements: Replacements = {}): string => {
        if (!key) {
            return '';
        }

        return Object.entries(replacements).reduce(
            (text, [name, value]) => text.replaceAll(`:${name}`, String(value)),
            translations[key] ?? key,
        );
    };

    return { t, locale, locales };
}
