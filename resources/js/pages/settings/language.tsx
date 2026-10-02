import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import { edit as editLanguage, update } from '@/routes/language';

export default function Language() {
    const { t, locale, locales } = useTranslation();

    return (
        <>
            <Head title={t('Language settings')} />

            <h1 className="sr-only">{t('Language settings')}</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t('Language settings')}
                    description={t(
                        'Choose the language used across the app for your account only',
                    )}
                />

                <div className="inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800">
                    {Object.entries(locales).map(([code, name]) => (
                        <button
                            key={code}
                            type="button"
                            onClick={() =>
                                router.patch(
                                    update.url(),
                                    { locale: code },
                                    { preserveScroll: true },
                                )
                            }
                            className={cn(
                                'rounded-md px-3.5 py-1.5 text-sm transition-colors',
                                locale === code
                                    ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                                    : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                            )}
                        >
                            {name}
                        </button>
                    ))}
                </div>
            </div>
        </>
    );
}

Language.layout = {
    breadcrumbs: [
        {
            title: 'Language settings',
            href: editLanguage(),
        },
    ],
};
