import { Head, Link, usePage } from "@inertiajs/react";
import { CheckCircle2 } from "lucide-react";
import AppLogoIcon from "@/components/app-logo-icon";
import { Button } from "@/components/ui/button";
import { useTranslation } from "@/hooks/use-translation";
import { dashboard, login, register } from "@/routes";

type Props = {
    content: {
        brand_name: string;
        hero_title: string;
        hero_subtitle: string;
        cta_label: string;
        footer_text: string;
        features: { title: string; description: string }[];
    };
};

export default function Welcome({ content }: Props) {
    const { t } = useTranslation();
    const { auth, currentTeam } = usePage().props;
    const dashboardUrl = currentTeam ? dashboard(currentTeam.slug) : "/";

    return (
        <>
            <Head title={content.brand_name} />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="border-b">
                    <nav className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                        <span className="flex items-center gap-2 font-semibold">
                            <AppLogoIcon className="size-6" />
                            {content.brand_name}
                        </span>
                        <div className="flex items-center gap-2">
                            {auth.user ? (
                                <Button asChild>
                                    <Link href={dashboardUrl}>
                                        {t("Dashboard")}
                                    </Link>
                                </Button>
                            ) : (
                                <>
                                    <Button variant="ghost" asChild>
                                        <Link href={login()}>{t("Log in")}</Link>
                                    </Button>
                                    <Button asChild>
                                        <Link href={register()}>
                                            {t("Register")}
                                        </Link>
                                    </Button>
                                </>
                            )}
                        </div>
                    </nav>
                </header>

                <main className="flex-1">
                    <section className="mx-auto max-w-4xl px-6 py-20 text-center md:py-28">
                        <h1 className="text-4xl font-bold tracking-tight md:text-6xl">
                            {content.hero_title}
                        </h1>
                        <p className="mx-auto mt-6 max-w-2xl text-lg text-muted-foreground">
                            {content.hero_subtitle}
                        </p>
                        <div className="mt-10 flex justify-center">
                            <Button size="lg" asChild>
                                <Link
                                    href={auth.user ? dashboardUrl : login()}
                                    data-test="landing-cta"
                                >
                                    {content.cta_label}
                                </Link>
                            </Button>
                        </div>
                    </section>

                    {content.features.length > 0 && (
                        <section className="border-t bg-muted/40">
                            <div className="mx-auto grid max-w-6xl gap-6 px-6 py-16 sm:grid-cols-2 lg:grid-cols-3">
                                {content.features.map((feature, index) => (
                                    <div
                                        key={index}
                                        className="rounded-lg border bg-card p-6"
                                    >
                                        <CheckCircle2 className="mb-4 size-6 text-primary" />
                                        <h3 className="font-semibold">
                                            {feature.title}
                                        </h3>
                                        <p className="mt-2 text-sm text-muted-foreground">
                                            {feature.description}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </section>
                    )}
                </main>

                <footer className="border-t py-6 text-center text-sm text-muted-foreground">
                    © {new Date().getFullYear()} {content.brand_name}.{" "}
                    {content.footer_text}
                </footer>
            </div>
        </>
    );
}
