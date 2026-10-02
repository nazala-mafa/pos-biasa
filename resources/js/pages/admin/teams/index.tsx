import { Head } from "@inertiajs/react";
import Heading from "@/components/heading";
import { Badge } from "@/components/ui/badge";
import { dashboard } from "@/routes/admin";
import { index } from "@/routes/admin/teams";

type TeamRow = {
    id: number;
    name: string;
    slug: string;
    isPersonal: boolean;
    membersCount: number;
    createdAt: string | null;
};

export default function AdminTeamsIndex({ teams }: { teams: TeamRow[] }) {
    return (
        <>
            <Head title="Teams" />
            <div className="flex flex-col space-y-6 p-4">
                <Heading
                    variant="small"
                    title="Teams"
                    description="All teams in the system"
                />
                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="p-3">Name</th>
                                <th className="p-3">Slug</th>
                                <th className="p-3">Members</th>
                                <th className="p-3">Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            {teams.map((team) => (
                                <tr key={team.id} className="border-t">
                                    <td className="p-3">
                                        {team.name}{" "}
                                        {team.isPersonal && (
                                            <Badge variant="secondary">
                                                Personal
                                            </Badge>
                                        )}
                                    </td>
                                    <td className="p-3">{team.slug}</td>
                                    <td className="p-3">{team.membersCount}</td>
                                    <td className="p-3">{team.createdAt}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

AdminTeamsIndex.layout = {
    breadcrumbs: [
        { title: "Admin", href: dashboard() },
        { title: "Teams", href: index() },
    ],
};
