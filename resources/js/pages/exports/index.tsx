import { Head, usePage } from '@inertiajs/react';
import {
    Building2,
    Download,
    FileSpreadsheet,
    FileText,
    ReceiptText,
    WalletCards,
    Zap,
} from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { translateKey, useI18n } from '@/lib/i18n';
import {
    download as downloadExport,
    index as exportsIndex,
} from '@/routes/exports';

const exports = [
    {
        key: 'properties',
        icon: Building2,
        title: 'exports.properties.title',
        description: 'exports.properties.description',
    },
    {
        key: 'leases',
        icon: FileText,
        title: 'exports.leases.title',
        description: 'exports.leases.description',
    },
    {
        key: 'payments',
        icon: WalletCards,
        title: 'exports.payments.title',
        description: 'exports.payments.description',
    },
    {
        key: 'expenses',
        icon: ReceiptText,
        title: 'exports.expenses.title',
        description: 'exports.expenses.description',
    },
    {
        key: 'utilities',
        icon: Zap,
        title: 'exports.utilities.title',
        description: 'exports.utilities.description',
    },
] as const;

export default function ExportsIndex() {
    const { currentTeam } = usePage().props;
    const { t } = useI18n();
    const teamSlug = currentTeam?.slug ?? '';

    return (
        <>
            <Head title={t('nav.exports')} />

            <h1 className="sr-only">{t('nav.exports')}</h1>

            <div className="mx-auto flex w-full max-w-[1180px] flex-col gap-5 p-3 sm:p-5 lg:p-6">
                <div className="rounded-2xl border border-border/70 bg-card/75 p-4 shadow-sm sm:p-5">
                    <Heading
                        variant="small"
                        title={t('nav.exports')}
                        description={t('exports.index.description')}
                    />
                </div>

                <div className="rounded-2xl border border-border/70 bg-card/90 p-4 shadow-sm sm:p-5">
                    <div className="flex items-start gap-3">
                        <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <FileSpreadsheet className="size-5" />
                        </div>
                        <div>
                            <h2 className="font-semibold">
                                {t('exports.index.title')}
                            </h2>
                            <p className="mt-0.5 text-sm text-muted-foreground">
                                {t('exports.index.note')}
                            </p>
                        </div>
                    </div>
                </div>

                <section className="grid gap-3 md:grid-cols-2">
                    {exports.map((item) => {
                        const Icon = item.icon;

                        return (
                            <article
                                key={item.key}
                                className="flex flex-col rounded-2xl border border-border/70 bg-card/90 p-4 shadow-sm"
                                data-test={`export-card-${item.key}`}
                            >
                                <div className="flex items-start gap-3">
                                    <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-muted">
                                        <Icon className="size-5" />
                                    </div>
                                    <div>
                                        <h2 className="font-semibold">
                                            {t(item.title)}
                                        </h2>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {t(item.description)}
                                        </p>
                                    </div>
                                </div>

                                <div className="mt-5">
                                    <Button asChild size="sm">
                                        <a
                                            href={
                                                downloadExport([
                                                    teamSlug,
                                                    item.key,
                                                ]).url
                                            }
                                            data-test={`export-download-${item.key}`}
                                        >
                                            <Download />
                                            {t('exports.download')}
                                        </a>
                                    </Button>
                                </div>
                            </article>
                        );
                    })}
                </section>
            </div>
        </>
    );
}

ExportsIndex.layout = (props: {
    currentTeam?: { slug: string } | null;
}) => ({
    breadcrumbs: [
        {
            title: translateKey('nav.exports'),
            href: props.currentTeam
                ? exportsIndex(props.currentTeam.slug)
                : '/',
        },
    ],
});
