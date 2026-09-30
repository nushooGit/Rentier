import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import {
    Download,
    FileText,
    FolderOpen,
    Plus,
    Search,
    Trash2,
    Upload,
    X,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import DateInput from '@/components/date-input';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { formatDateLong, formatDateRangeLong } from '@/lib/date';
import { translateKey, useI18n } from '@/lib/i18n';
import { documentCategoryLabel } from '@/pages/documents/labels';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    destroy,
    download,
    index,
    store,
} from '@/routes/documents';
import { create as createProperty } from '@/routes/properties';
import type {
    DocumentCategoryOption,
    DocumentLeaseOption,
    DocumentPropertyOption,
    RentierDocument,
} from '@/types';

type Props = {
    documents: RentierDocument[];
    categories: DocumentCategoryOption[];
    properties: DocumentPropertyOption[];
    leases: DocumentLeaseOption[];
};

const selectClassName =
    'border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-1 text-sm shadow-xs transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50';

function localToday(): string {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${day}`;
}

function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${Math.round(bytes / 1024)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function normalizeSearchText(value: string): string {
    return value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase('ro-RO');
}

export default function DocumentsIndex({
    documents,
    categories,
    properties,
    leases,
}: Props) {
    const { currentTeam } = usePage().props;
    const { t } = useI18n();
    const currentTeamSlug = currentTeam?.slug ?? '';
    const [selectedPropertyId, setSelectedPropertyId] = useState('');
    const [query, setQuery] = useState('');
    const [categoryFilter, setCategoryFilter] = useState('all');
    const [propertyFilter, setPropertyFilter] = useState('all');
    const [expiryFilter, setExpiryFilter] = useState('all');
    const [selectedFileName, setSelectedFileName] = useState<string | null>(null);

    const propertyLeases = useMemo(
        () =>
            leases.filter(
                (lease) => lease.property_id.toString() === selectedPropertyId,
            ),
        [leases, selectedPropertyId],
    );

    const filteredDocuments = useMemo(() => {
        const normalizedQuery = normalizeSearchText(query.trim());
        const today = localToday();

        return documents.filter((document) => {
            const matchesQuery =
                normalizedQuery === '' ||
                [
                    document.original_name,
                    document.category_label,
                    documentCategoryLabel(document.category),
                    document.property?.name ?? '',
                    document.property?.city ?? '',
                    document.lease?.renter_name ?? '',
                ].some((value) =>
                    normalizeSearchText(value).includes(normalizedQuery),
                );
            const matchesCategory =
                categoryFilter === 'all' ||
                document.category === categoryFilter;
            const matchesProperty =
                propertyFilter === 'all' ||
                document.property?.id.toString() === propertyFilter;
            const matchesExpiry =
                expiryFilter === 'all' ||
                (expiryFilter === 'with_expiry' &&
                    document.expires_on !== null) ||
                (expiryFilter === 'without_expiry' &&
                    document.expires_on === null) ||
                (expiryFilter === 'expired' &&
                    document.expires_on !== null &&
                    document.expires_on < today) ||
                (expiryFilter === 'valid' &&
                    document.expires_on !== null &&
                    document.expires_on >= today);

            return (
                matchesQuery &&
                matchesCategory &&
                matchesProperty &&
                matchesExpiry
            );
        });
    }, [
        categoryFilter,
        documents,
        expiryFilter,
        propertyFilter,
        query,
    ]);

    const filtersAreActive =
        query.trim() !== '' ||
        categoryFilter !== 'all' ||
        propertyFilter !== 'all' ||
        expiryFilter !== 'all';

    const clearFilters = () => {
        setQuery('');
        setCategoryFilter('all');
        setPropertyFilter('all');
        setExpiryFilter('all');
    };

    const deleteDocument = (document: RentierDocument) => {
        if (
            !window.confirm(
                t('documents.deleteConfirm', { name: document.original_name }),
            )
        ) {
            return;
        }

        router.delete(destroy([currentTeamSlug, document.id]).url, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title={t('nav.documents')} />

            <h1 className="sr-only">{t('nav.documents')}</h1>

            <div className="mx-auto flex w-full max-w-7xl flex-col gap-4 p-3 sm:p-4">
                <Heading
                    variant="small"
                    title={t('nav.documents')}
                    description={t('documents.index.description')}
                />

                <section className="rounded-2xl border border-border/70 bg-card/90 p-4 shadow-sm sm:p-5">
                    <div className="mb-4 flex items-start gap-3">
                        <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <Upload className="size-5" />
                        </div>
                        <div>
                            <h2 className="font-semibold">{t('documents.upload.title')}</h2>
                            <p className="mt-0.5 text-sm text-muted-foreground">
                                {t('documents.upload.formats')}
                            </p>
                        </div>
                    </div>

                    {properties.length === 0 ? (
                        <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                            <p>
                                {t('documents.noPropertyTitle')}
                            </p>
                            <Button className="mt-3" size="sm" asChild>
                                <Link href={createProperty(currentTeamSlug)}>
                                    <Plus />
                                    {t('documents.addProperty')}
                                </Link>
                            </Button>
                        </div>
                    ) : (
                        <Form
                            action={store(currentTeamSlug).url}
                            method="post"
                            resetOnSuccess
                            options={{ preserveScroll: true }}
                            className="grid gap-3 md:grid-cols-2 xl:grid-cols-3"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-1.5 md:col-span-2 xl:col-span-3">
                                        <Label htmlFor="file">{t('documents.file')}</Label>
                                        <input
                                            id="file"
                                            name="file"
                                            type="file"
                                            required
                                            accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx"
                                            data-test="document-file-input"
                                            className="sr-only"
                                            onChange={(event) =>
                                                setSelectedFileName(
                                                    event.target.files?.[0]?.name ??
                                                        null,
                                                )
                                            }
                                        />
                                        <label
                                            htmlFor="file"
                                            className="group flex min-h-32 cursor-pointer flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-muted/25 px-5 py-6 text-center transition hover:border-primary/45 hover:bg-primary/[0.04] focus-within:ring-2 focus-within:ring-ring"
                                        >
                                            <span className="flex size-11 items-center justify-center rounded-2xl bg-primary/10 text-primary transition group-hover:scale-105">
                                                <Upload className="size-5" aria-hidden="true" />
                                            </span>
                                            <span className="mt-3 text-sm font-semibold">
                                                {selectedFileName ??
                                                    t('documents.upload.choose')}
                                            </span>
                                            <span className="mt-1 text-xs leading-5 text-muted-foreground">
                                                {t('documents.upload.formats')}
                                            </span>
                                            <span className="mt-2 text-xs font-medium text-primary">
                                                {t('documents.upload.action')}
                                            </span>
                                        </label>
                                        <InputError message={errors.file} />
                                    </div>

                                    <div className="grid gap-1.5">
                                        <Label htmlFor="category">{t('documents.category')}</Label>
                                        <select
                                            id="category"
                                            name="category"
                                            required
                                            defaultValue="lease_contract"
                                            className={selectClassName}
                                        >
                                            {categories.map((category) => (
                                                <option
                                                    key={category.value}
                                                    value={category.value}
                                                >
                                                    {documentCategoryLabel(category.value)}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.category} />
                                    </div>

                                    <div className="grid gap-1.5">
                                        <Label htmlFor="property_id">
                                            {t('documents.property')}
                                        </Label>
                                        <select
                                            id="property_id"
                                            name="property_id"
                                            required
                                            value={selectedPropertyId}
                                            onChange={(event) =>
                                                setSelectedPropertyId(
                                                    event.target.value,
                                                )
                                            }
                                            className={selectClassName}
                                        >
                                            <option value="" disabled>
                                                {t('documents.chooseProperty')}
                                            </option>
                                            {properties.map((property) => (
                                                <option
                                                    key={property.id}
                                                    value={property.id}
                                                >
                                                    {property.name} ·{' '}
                                                    {property.city}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.property_id} />
                                    </div>

                                    <div className="grid gap-1.5">
                                        <Label htmlFor="lease_id">
                                            {t('documents.lease')}
                                            <span className="ml-1 font-normal text-muted-foreground">
                                                {t('documents.optional')}
                                            </span>
                                        </Label>
                                        <select
                                            id="lease_id"
                                            name="lease_id"
                                            disabled={selectedPropertyId === ''}
                                            defaultValue=""
                                            className={selectClassName}
                                            key={selectedPropertyId}
                                        >
                                            <option value="">
                                                {t('documents.noLease')}
                                            </option>
                                            {propertyLeases.map((lease) => (
                                                <option
                                                    key={lease.id}
                                                    value={lease.id}
                                                >
                                                    {lease.renter_name} ·{' '}
                                                    {formatDateRangeLong(
                                                        lease.start_date,
                                                        lease.end_date,
                                                    )}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.lease_id} />
                                    </div>

                                    <div className="grid gap-1.5">
                                        <Label htmlFor="document_date">
                                            {t('documents.documentDate')}
                                        </Label>
                                        <DateInput
                                            id="document_date"
                                            name="document_date"
                                            defaultValue={localToday()}
                                            required
                                        />
                                        <InputError
                                            message={errors.document_date}
                                        />
                                    </div>

                                    <div className="grid gap-1.5">
                                        <Label htmlFor="expires_on">
                                            {t('documents.expiresAt')}
                                            <span className="ml-1 font-normal text-muted-foreground">
                                                {t('documents.optional')}
                                            </span>
                                        </Label>
                                        <DateInput
                                            id="expires_on"
                                            name="expires_on"
                                        />
                                        <InputError message={errors.expires_on} />
                                    </div>

                                    <div className="flex items-end md:col-span-2 xl:col-span-1">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            data-test="document-upload-button"
                                            className="w-full"
                                        >
                                            <Upload />
                                            {processing
                                                ? t('documents.upload.loading')
                                                : t('documents.upload.submit')}
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    )}
                </section>

                <section>
                    <div className="mb-3 flex flex-col gap-3">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <h2 className="font-semibold">
                                    {t('documents.saved')}
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    {filtersAreActive
                                        ? t('documents.filteredCount', { filtered: filteredDocuments.length, total: documents.length })
                                        : documents.length === 1
                                          ? t('documents.countOne')
                                          : t('documents.countMany', { count: documents.length })}
                                </p>
                            </div>
                            {filtersAreActive ? (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={clearFilters}
                                    data-test="document-clear-filters"
                                >
                                    <X />
                                    {t('documents.reset')}
                                </Button>
                            ) : null}
                        </div>

                        {documents.length > 0 ? (
                            <div
                                className="grid gap-2.5 rounded-2xl border border-border/70 bg-card/80 p-3.5 shadow-sm md:grid-cols-2 xl:grid-cols-4"
                                data-test="document-filters"
                            >
                                <div className="relative md:col-span-2 xl:col-span-1">
                                    <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        value={query}
                                        onChange={(event) =>
                                            setQuery(event.target.value)
                                        }
                                        placeholder={t('documents.search.placeholder')}
                                        className="pl-9"
                                        aria-label={t('documents.search.label')}
                                        data-test="document-search-input"
                                    />
                                </div>

                                <select
                                    value={categoryFilter}
                                    onChange={(event) =>
                                        setCategoryFilter(event.target.value)
                                    }
                                    className={selectClassName}
                                    aria-label={t('documents.filter.category')}
                                    data-test="document-category-filter"
                                >
                                    <option value="all">{t('documents.allCategories')}</option>
                                    {categories.map((category) => (
                                        <option
                                            key={category.value}
                                            value={category.value}
                                        >
                                            {documentCategoryLabel(category.value)}
                                        </option>
                                    ))}
                                </select>

                                <select
                                    value={propertyFilter}
                                    onChange={(event) =>
                                        setPropertyFilter(event.target.value)
                                    }
                                    className={selectClassName}
                                    aria-label={t('documents.filter.property')}
                                    data-test="document-property-filter"
                                >
                                    <option value="all">
                                        {t('documents.allProperties')}
                                    </option>
                                    {properties.map((property) => (
                                        <option
                                            key={property.id}
                                            value={property.id}
                                        >
                                            {property.name} · {property.city}
                                        </option>
                                    ))}
                                </select>

                                <select
                                    value={expiryFilter}
                                    onChange={(event) =>
                                        setExpiryFilter(event.target.value)
                                    }
                                    className={selectClassName}
                                    aria-label={t('documents.filter.expiry')}
                                    data-test="document-expiry-filter"
                                >
                                    <option value="all">
                                        {t('documents.anyExpiry')}
                                    </option>
                                    <option value="with_expiry">
                                        {t('documents.withExpiry')}
                                    </option>
                                    <option value="without_expiry">
                                        {t('documents.withoutExpiry')}
                                    </option>
                                    <option value="valid">
                                        {t('documents.validFuture')}
                                    </option>
                                    <option value="expired">{t('documents.expired')}</option>
                                </select>
                            </div>
                        ) : null}
                    </div>

                    {documents.length === 0 ? (
                        <div className="rounded-xl border border-dashed p-8 text-center">
                            <FolderOpen className="mx-auto size-9 text-muted-foreground" />
                            <h3 className="mt-3 font-medium">
                                {t('documents.emptyTitle')}
                            </h3>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {t('documents.emptyDescription')}
                            </p>
                        </div>
                    ) : filteredDocuments.length === 0 ? (
                        <div className="rounded-xl border border-dashed p-8 text-center">
                            <Search className="mx-auto size-9 text-muted-foreground" />
                            <h3 className="mt-3 font-medium">
                                {t('documents.noResultsTitle')}
                            </h3>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {t('documents.noResultsDescription')}
                            </p>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                className="mt-4"
                                onClick={clearFilters}
                            >
                                {t('documents.resetFilters')}
                            </Button>
                        </div>
                    ) : (
                        <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            {filteredDocuments.map((document) => (
                                <article
                                    key={document.id}
                                    className="flex flex-col rounded-2xl border border-border/70 bg-card/90 p-4 shadow-sm transition-all hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-md"
                                    data-test="document-card"
                                >
                                    <div className="flex items-start gap-3">
                                        <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-muted">
                                            <FileText className="size-5" />
                                        </div>
                                        <div className="min-w-0">
                                            <p
                                                className="truncate font-medium"
                                                title={document.original_name}
                                            >
                                                {document.original_name}
                                            </p>
                                            <p className="mt-0.5 text-sm text-muted-foreground">
                                                {documentCategoryLabel(document.category)}
                                            </p>
                                        </div>
                                    </div>

                                    <dl className="mt-4 grid gap-2 text-sm">
                                        <div className="flex justify-between gap-3">
                                            <dt className="text-muted-foreground">
                                                {t('documents.property')}
                                            </dt>
                                            <dd className="text-right font-medium">
                                                {document.property
                                                    ? `${document.property.name} · ${document.property.city}`
                                                    : t('documents.deletedProperty')}
                                            </dd>
                                        </div>
                                        {document.lease ? (
                                            <div className="flex justify-between gap-3">
                                                <dt className="text-muted-foreground">
                                                    {t('documents.lease')}
                                                </dt>
                                                <dd className="text-right font-medium">
                                                    {document.lease.renter_name}
                                                </dd>
                                            </div>
                                        ) : null}
                                        <div className="flex justify-between gap-3">
                                            <dt className="text-muted-foreground">
                                                {t('documents.date')}
                                            </dt>
                                            <dd className="text-right">
                                                {formatDateLong(document.document_date)}
                                            </dd>
                                        </div>
                                        {document.expires_on ? (
                                            <div className="flex justify-between gap-3">
                                                <dt className="text-muted-foreground">
                                                    {t('documents.expires')}
                                                </dt>
                                                <dd className="text-right">
                                                    {formatDateLong(document.expires_on)}
                                                </dd>
                                            </div>
                                        ) : null}
                                        <div className="flex justify-between gap-3">
                                            <dt className="text-muted-foreground">
                                                {t('documents.size')}
                                            </dt>
                                            <dd>{formatBytes(document.size_bytes)}</dd>
                                        </div>
                                    </dl>

                                    <div className="mt-4 flex gap-2 border-t pt-3">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            className="flex-1"
                                            asChild
                                        >
                                            <a
                                                href={
                                                    download([
                                                        currentTeamSlug,
                                                        document.id,
                                                    ]).url
                                                }
                                                data-test="document-download-link"
                                            >
                                                <Download />
                                                {t('documents.download')}
                                            </a>
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            type="button"
                                            aria-label={t('documents.delete')}
                                            data-test="document-delete-button"
                                            onClick={() =>
                                                deleteDocument(document)
                                            }
                                        >
                                            <Trash2 />
                                        </Button>
                                    </div>
                                </article>
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

DocumentsIndex.layout = (props: {
    currentTeam?: { slug: string } | null;
}) => ({
    breadcrumbs: [
        {
            title: translateKey('nav.documents'),
            href: props.currentTeam ? index(props.currentTeam.slug) : '/',
        },
    ],
});
