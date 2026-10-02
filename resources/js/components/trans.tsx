import { Fragment } from 'react';
import type { ReactNode } from 'react';
import { useTranslation } from '@/hooks/use-translation';

/**
 * Translate a sentence that embeds React nodes, e.g.
 * <Trans text="Delete :name?" values={{ name: <strong>{name}</strong> }} />
 */
export default function Trans({
    text,
    values,
}: {
    text: string;
    values: Record<string, ReactNode>;
}) {
    const { t } = useTranslation();
    const pattern = new RegExp(`:(${Object.keys(values).join('|')})\\b`, 'g');

    return (
        <>
            {t(text)
                .split(pattern)
                .map((part, index) => (
                    <Fragment key={index}>
                        {index % 2 === 1 ? values[part] : part}
                    </Fragment>
                ))}
        </>
    );
}

/**
 * Translate a plain string where a hook cannot be called, e.g. in
 * module-level table column definitions: <T text="Active" />
 */
export function T({ text }: { text: string }) {
    const { t } = useTranslation();

    return <>{t(text)}</>;
}
