import { Form } from "@inertiajs/react";
import FormLayout from "@/components/form-layout";
import InputError from "@/components/input-error";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useCurrentTeamSlug } from "@/hooks/use-current-team-slug";
import { index, store } from "@/routes/shifts";
import { useTranslation } from "@/hooks/use-translation";

export default function ShiftsForm() {
    const { t } = useTranslation();
    const team = useCurrentTeamSlug();

    return (
        <Form {...store.form(team)}>
            {({ errors, processing }) => (
                <FormLayout
                    size="sm"
                    title={t("Open shift")}
                    description={t("Count the cash in the drawer before you start")}
                    processing={processing}
                    cancelHref={index(team).url}
                    saveTestId="shift-open-button"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="opening_cash">{t("Opening cash")}</Label>
                        <Input
                            id="opening_cash"
                            name="opening_cash"
                            type="number"
                            step="0.01"
                            min="0"
                            defaultValue="0"
                            required
                        />
                        <InputError message={errors.opening_cash} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="note">{t("Note")}</Label>
                        <Input id="note" name="note" maxLength={255} />
                        <InputError message={errors.note} />
                    </div>
                </FormLayout>
            )}
        </Form>
    );
}

ShiftsForm.layout = (props: { currentTeam: { slug: string } | null }) => ({
    breadcrumbs: [
        { title: "Cashier Shifts", href: index(props.currentTeam?.slug ?? "") },
    ],
});
