import { useForm } from "@inertiajs/react";
import { Plus, Trash2 } from "lucide-react";
import type { FormEvent } from "react";
import FormLayout, { SidebarCard } from "@/components/form-layout";
import InputError from "@/components/input-error";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useTranslation } from "@/hooks/use-translation";
import { dashboard } from "@/routes/admin";
import { edit, update } from "@/routes/admin/landing-page";

type Feature = { title: string; description: string };

type Content = {
    brand_name: string;
    hero_title: string;
    hero_subtitle: string;
    cta_label: string;
    footer_text: string;
    features: Feature[];
};

type Props = {
    content: Content;
    maxFeatures: number;
};

const textareaClass =
    "border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-20 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]";

export default function LandingPageSettings({ content, maxFeatures }: Props) {
    const { t } = useTranslation();
    const form = useForm<Content>(content);
    const errors = form.errors as Record<string, string | undefined>;

    const setFeature = (index: number, field: keyof Feature, value: string) => {
        form.setData(
            "features",
            form.data.features.map((feature, i) =>
                i === index ? { ...feature, [field]: value } : feature,
            ),
        );
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(update.url());
    };

    return (
        <form onSubmit={submit}>
            <FormLayout
                size="md"
                title="Landing page"
                description="Edit the content shown on the public home page"
                processing={form.processing}
                cancelHref={dashboard().url}
                saveTestId="landing-page-save-button"
                sidebar={
                    <SidebarCard title="Preview">
                        <p className="text-sm text-muted-foreground">
                            {t("Open the public home page to see the result.")}
                        </p>
                        <Button variant="outline" className="w-full" asChild>
                            <a href="/" target="_blank" rel="noreferrer">
                                {t("Open landing page")}
                            </a>
                        </Button>
                    </SidebarCard>
                }
            >
                <div className="grid gap-2">
                    <Label htmlFor="brand_name">{t("Brand name")}</Label>
                    <Input
                        id="brand_name"
                        value={form.data.brand_name}
                        onChange={(e) =>
                            form.setData("brand_name", e.target.value)
                        }
                        required
                    />
                    <InputError message={errors.brand_name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="hero_title">{t("Headline")}</Label>
                    <Input
                        id="hero_title"
                        value={form.data.hero_title}
                        onChange={(e) =>
                            form.setData("hero_title", e.target.value)
                        }
                        required
                    />
                    <InputError message={errors.hero_title} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="hero_subtitle">{t("Sub headline")}</Label>
                    <textarea
                        id="hero_subtitle"
                        className={textareaClass}
                        value={form.data.hero_subtitle}
                        onChange={(e) =>
                            form.setData("hero_subtitle", e.target.value)
                        }
                        required
                    />
                    <InputError message={errors.hero_subtitle} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="cta_label">{t("Button label")}</Label>
                    <Input
                        id="cta_label"
                        value={form.data.cta_label}
                        onChange={(e) =>
                            form.setData("cta_label", e.target.value)
                        }
                        required
                    />
                    <InputError message={errors.cta_label} />
                </div>

                <div className="space-y-3">
                    <div className="flex items-center justify-between">
                        <Label>{t("Features")}</Label>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={form.data.features.length >= maxFeatures}
                            onClick={() =>
                                form.setData("features", [
                                    ...form.data.features,
                                    { title: "", description: "" },
                                ])
                            }
                        >
                            <Plus /> {t("Add feature")}
                        </Button>
                    </div>
                    {form.data.features.map((feature, index) => (
                        <div
                            key={index}
                            className="grid gap-2 rounded-md border p-4"
                        >
                            <div className="flex items-center gap-2">
                                <Input
                                    aria-label={t("Title")}
                                    placeholder={t("Title")}
                                    value={feature.title}
                                    onChange={(e) =>
                                        setFeature(index, "title", e.target.value)
                                    }
                                    required
                                />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label={t("Delete")}
                                    onClick={() =>
                                        form.setData(
                                            "features",
                                            form.data.features.filter(
                                                (_, i) => i !== index,
                                            ),
                                        )
                                    }
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                            <InputError
                                message={errors[`features.${index}.title`]}
                            />
                            <textarea
                                aria-label={t("Description")}
                                placeholder={t("Description")}
                                className={textareaClass}
                                value={feature.description}
                                onChange={(e) =>
                                    setFeature(
                                        index,
                                        "description",
                                        e.target.value,
                                    )
                                }
                                required
                            />
                            <InputError
                                message={errors[`features.${index}.description`]}
                            />
                        </div>
                    ))}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="footer_text">{t("Footer text")}</Label>
                    <Input
                        id="footer_text"
                        value={form.data.footer_text}
                        onChange={(e) =>
                            form.setData("footer_text", e.target.value)
                        }
                    />
                    <InputError message={errors.footer_text} />
                </div>
            </FormLayout>
        </form>
    );
}

LandingPageSettings.layout = {
    breadcrumbs: [
        { title: "Admin", href: dashboard() },
        { title: "Landing page", href: edit() },
    ],
};
