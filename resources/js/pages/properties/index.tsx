import { Head, Link, router, usePage } from '@inertiajs/react';
import { Eye, Pencil, Plus, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { translateKey, useI18n } from '@/lib/i18n';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import {
    propertyStatusLabel,
    propertyTypeLabel,
} from '@/pages/properties/labels';
import { create, destroy, edit, index, show } from '@/routes/properties';
import type {
    Property,
    PropertyOption,
    PropertyStatus,
    PropertyType,
    RentPaymentStatusBadge,
    RentPaymentStatusKey,
} from '@/types';

type Props = {
    properties: Property[];
    propertyTypes: PropertyOption<PropertyType>[];
    propertyStatuses: PropertyOption<PropertyStatus>[];
};

function formatMoney(amount?: string | null, currency = 'RON') {
    if (!amount) {
        return translateKey('properties.index.rentUnset');
    }

    return `${Number(amount).toLocaleString(undefined, {
        maximumFractionDigits: 2,
        minimumFractionDigits: 0,
    })} ${currency}`;
}

function hasPositiveAmount(amount?: string | null) {
    return Number(amount ?? 0) > 0;
}

const rentStatusClassNames: Record<RentPaymentStatusKey, string> = {
    paid: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    partial: 'border-amber-200 bg-amber-50 text-amber-700',
    partial_overdue: 'border-red-200 bg-red-50 text-red-700',
    due_today: 'border-sky-200 bg-sky-50 text-sky-700',
    upcoming: 'border-slate-200 bg-slate-50 text-slate-700',
    overdue: 'border-red-200 bg-red-50 text-red-700',
};

function rentStatusBadgeClassName(badge: RentPaymentStatusBadge) {
    return rentStatusClassNames[badge.tone as RentPaymentStatusKey] ?? '';
}

export default function PropertiesIndex({ properties }: Props) {
    const { currentTeam } = usePage().props;
    const { t } = useI18n();
    const currentTeamSlug = currentTeam?.slug ?? '';

    const rentBadgeLabel = (
        property: Property,
        badge: RentPaymentStatusBadge,
    ) => {
        const status = property.rent_payment_status;

        if (!status) return '';
        if (badge.key === 'partial') return t('properties.rent.partial');
        if (badge.key === 'overdue_months') {
            return t('properties.rent.overdueMonths', {
                count: status.overdue_month_count,
            });
        }
        if (badge.key === 'arrears') {
            return t('properties.rent.arrears', {
                amount: formatMoney(status.arrears_amount, property.currency),
            });
        }
        if (badge.key === 'overdue') {
            return status.days === 1
                ? t('properties.rent.overdueOne')
                : t('properties.rent.overdueMany', {
                      days: status.days ?? 0,
                  });
        }

        switch (status.key) {
            case 'paid':
                return Number(status.rent_deduction_amount) > 0
                    ? t('properties.rent.covered')
                    : t('properties.rent.paid');
            case 'partial':
            case 'partial_overdue':
                return Number(status.rent_deduction_amount) > 0
                    ? t('properties.rent.partialCovered')
                    : t('properties.rent.partial');
            case 'due_today':
                return t('properties.rent.dueToday');
            case 'upcoming':
                return status.days === 1
                    ? t('properties.rent.upcomingOne')
                    : t('properties.rent.upcomingMany', {
                          days: status.days ?? 0,
                      });
            case 'overdue':
                return status.days === 1
                    ? t('properties.rent.overdueOne')
                    : t('properties.rent.overdueMany', {
                          days: status.days ?? 0,
                      });
        }
    };

    const advanceNoticeLabel = (
        property: Property,
        notice: NonNullable<
            Property['rent_payment_status']
        >['advance_notices'][number],
    ) => {
        const period = formatMonthYear(`${notice.period_key}-01`);

        return notice.key === 'paid_through'
            ? t('properties.rent.paidThrough', { period })
            : t('properties.rent.partialAdvance', {
                  period,
                  amount: formatMoney(notice.amount, property.currency),
                  expected: formatMoney(
                      notice.expected_amount,
                      property.currency,
                  ),
              });
    };

    const deleteProperty = (property: Property) => {
        if (!window.confirm(t('properties.index.deleteConfirm'))) {
            return;
        }

        router.delete(destroy([currentTeamSlug, property.id]).url);
    };

    return (
        <>
            <Head title={t('nav.properties')} />

            <h1 className="sr-only">{t('nav.properties')}</h1>

            <div className="mx-auto flex w-full max-w-[1480px] flex-col gap-5 p-3 sm:p-5 lg:p-6">
                <div className="flex flex-col gap-4 rounded-2xl border border-border/70 bg-card/75 p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
                    <Heading
                        variant="small"
                        title={t('nav.properties')}
                        description={t('properties.index.description')}
                    />

                    <Button asChild data-test="property-create-link">
                        <Link href={create(currentTeamSlug)}>
                            <Plus /> {t('properties.index.new')}
                        </Link>
                    </Button>
                </div>

                {properties.length > 0 ? (
                    <div className="grid gap-3.5 md:grid-cols-2 xl:grid-cols-3">
                        {properties.map((property) => (
                            <article
                                key={property.id}
                                className="flex flex-col overflow-hidden rounded-2xl border border-border/70 bg-card/90 shadow-sm transition-all focus-within:border-primary/35 hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-md"
                                data-test="property-card"
                            >
                                <Link
                                    href={show([currentTeamSlug, property.id])}
                                    className="flex flex-1 cursor-pointer flex-col gap-3 rounded-2xl p-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:p-5"
                                    data-test="property-card-link"
                                    aria-label={t('properties.index.view', { name: property.name })}
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <h2 className="truncate text-base font-medium">
                                                {property.name}
                                            </h2>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {propertyTypeLabel(
                                                    property.type,
                                                )}{' '}
                                                · {property.city}
                                                {property.county_or_sector
                                                    ? `, ${property.county_or_sector}`
                                                    : ''}
                                            </p>
                                        </div>
                                        <Badge variant="secondary">
                                            {propertyStatusLabel(
                                                property.status,
                                            )}
                                        </Badge>
                                    </div>

                                    <div className="grid gap-0.5 text-sm">
                                        <span className="font-medium">
                                            {formatMoney(
                                                property.monthly_rent_amount,
                                                property.currency,
                                            )}
                                        </span>
                                        <span className="text-muted-foreground">
                                            {property.address_line}
                                        </span>
                                        {property.rent_payment_status ? (
                                            <div className="mt-1 grid gap-1">
                                                <div className="flex flex-wrap gap-1">
                                                    {property.rent_payment_status.badges.map(
                                                        (badge) => (
                                                            <Badge
                                                                key={badge.key}
                                                                variant="outline"
                                                                className={`w-fit ${rentStatusBadgeClassName(badge)}`}
                                                            >
                                                                {rentBadgeLabel(property, badge)}
                                                            </Badge>
                                                        ),
                                                    )}
                                                </div>
                                                {hasPositiveAmount(
                                                    property.rent_payment_status
                                                        .rent_deduction_amount,
                                                ) ? (
                                                    <span className="text-xs text-muted-foreground">
                                                        {t('properties.rent.deducted', {
                                                            amount: formatMoney(
                                                                property.rent_payment_status.rent_deduction_amount,
                                                                property.currency,
                                                            ),
                                                        })}

                                                    </span>
                                                ) : null}
                                                {hasPositiveAmount(
                                                    property.rent_payment_status
                                                        .collected_amount,
                                                ) ? (
                                                    <span className="text-xs text-muted-foreground">
                                                        {t('properties.rent.collected', {
                                                            amount: formatMoney(
                                                                property.rent_payment_status.collected_amount,
                                                                property.currency,
                                                            ),
                                                        })}

                                                    </span>
                                                ) : null}
                                                {((
                                                    property.rent_payment_status
                                                        .advance_notices ?? []
                                                ).length > 0
                                                    ? property
                                                          .rent_payment_status
                                                          .advance_notices
                                                    : property
                                                            .rent_payment_status
                                                            .advance_notice
                                                      ? [
                                                            property
                                                                .rent_payment_status
                                                                .advance_notice,
                                                        ]
                                                      : []
                                                ).map((notice) => (
                                                    <span
                                                        key={`${notice.key}-${notice.period_key}`}
                                                        className="text-xs font-medium text-emerald-700"
                                                    >
                                                        {advanceNoticeLabel(property, notice)}
                                                    </span>
                                                ))}
                                            </div>
                                        ) : null}
                                    </div>
                                </Link>

                                <TooltipProvider>
                                    <div className="mt-auto flex justify-end gap-1.5 border-t border-border/60 bg-muted/20 px-3 py-2.5">
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    asChild
                                                    data-test="property-view-link"
                                                >
                                                    <Link
                                                        href={show([
                                                            currentTeamSlug,
                                                            property.id,
                                                        ])}
                                                    >
                                                        <Eye className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>{t('properties.index.view', { name: property.name })}</p>
                                            </TooltipContent>
                                        </Tooltip>

                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    asChild
                                                    data-test="property-edit-link"
                                                >
                                                    <Link
                                                        href={edit([
                                                            currentTeamSlug,
                                                            property.id,
                                                        ])}
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>{t('properties.index.edit')}</p>
                                            </TooltipContent>
                                        </Tooltip>

                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    type="button"
                                                    onClick={() =>
                                                        deleteProperty(property)
                                                    }
                                                    aria-label={t('properties.index.delete')}
                                                    data-test="property-delete-button"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>{t('properties.index.delete')}</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </div>
                                </TooltipProvider>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="rounded-2xl border border-dashed border-border bg-card/50 p-8 text-center shadow-sm">
                        <h2 className="text-base font-medium">
                            {t('properties.index.emptyCurrent')}
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {t('properties.index.emptyCurrentDescription')}
                        </p>
                        <Button className="mt-4" asChild>
                            <Link href={create(currentTeamSlug)}>
                                <Plus /> {t('properties.index.new')}
                            </Link>
                        </Button>
                    </div>
                )}
            </div>
        </>
    );
}

PropertiesIndex.layout = (props: {
    currentTeam?: { slug: string } | null;
}) => ({
    breadcrumbs: [
        {
            title: translateKey('nav.properties'),
            href: props.currentTeam ? index(props.currentTeam.slug) : '/',
        },
    ],
});
