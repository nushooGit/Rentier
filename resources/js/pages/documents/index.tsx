import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import {
    Download,
    FileText,
    FolderOpen,
    Plus,
    Trash2,
    Upload,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import DateInput from '@/components/date-input';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('ro-RO', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(new Date(`${value}T12:00:00`));
}

export default function DocumentsIndex({
    documents,
    categories,
    properties,
    leases,
}: Props) {
    const { currentTeam } = usePage().props;
    const currentTeamSlug = currentTeam?.slug ?? '';
    const [selectedPropertyId, setSelectedPropertyId] = useState('');

    const propertyLeases = useMemo(
        () =>
            leases.filter(
                (lease) => lease.property_id.toString() === selectedPropertyId,
            ),
        [leases, selectedPropertyId],
    );

    const deleteDocument = (document: RentierDocument) => {
        if (
            !window.confirm(
                `Sigur vrei să ștergi „${document.original_name}”? Fișierul va fi șters definitiv.`,
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
            <Head title="Documente" />

            <h1 className="sr-only">Documente</h1>

            <div className="mx-auto flex w-full max-w-7xl flex-col gap-4 p-3 sm:p-4">
                <Heading
                    variant="small"
                    title="Documente"
                    description="Păstrează documentele proprietăților și contractelor într-un singur loc"
                />

                <section className="rounded-xl border bg-card p-4 shadow-sm">
                    <div className="mb-4 flex items-start gap-3">
                        <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <Upload className="size-5" />
                        </div>
                        <div>
                            <h2 className="font-semibold">Încarcă document</h2>
                            <p className="mt-0.5 text-sm text-muted-foreground">
                                PDF, JPG, PNG, WebP sau Word · maximum 20 MB.
                            </p>
                        </div>
                    </div>

                    {properties.length === 0 ? (
                        <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                            <p>
                                Ai nevoie de cel puțin o proprietate înainte să
                                poți încărca documente.
                            </p>
                            <Button className="mt-3" size="sm" asChild>
                                <Link href={createProperty(currentTeamSlug)}>
                                    <Plus />
                                    Adaugă proprietate
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
                                        <Label htmlFor="file">Fișier</Label>
                                        <input
                                            id="file"
                                            name="file"
                                            type="file"
                                            required
                                            accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx"
                                            data-test="document-file-input"
                                            className="file:bg-primary file:text-primary-foreground hover:file:bg-primary/90 block w-full rounded-md border border-input bg-background text-sm text-muted-foreground file:mr-3 file:border-0 file:px-3 file:py-2 file:text-sm file:font-medium"
                                        />
                                        <InputError message={errors.file} />
                                    </div>

                                    <div className="grid gap-1.5">
                                        <Label htmlFor="category">Categorie</Label>
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
                                                    {category.label}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.category} />
                                    </div>

                                    <div className="grid gap-1.5">
                                        <Label htmlFor="property_id">
                                            Proprietate
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
                                                Alege proprietatea
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
                                            Contract
                                            <span className="ml-1 font-normal text-muted-foreground">
                                                (opțional)
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
                                                Fără contract asociat
                                            </option>
                                            {propertyLeases.map((lease) => (
                                                <option
                                                    key={lease.id}
                                                    value={lease.id}
                                                >
                                                    {lease.label}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.lease_id} />
                                    </div>

                                    <div className="grid gap-1.5">
                                        <Label htmlFor="document_date">
                                            Data documentului
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
                                            Expiră la
                                            <span className="ml-1 font-normal text-muted-foreground">
                                                (opțional)
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
                                                ? 'Se încarcă…'
                                                : 'Încarcă documentul'}
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    )}
                </section>

                <section>
                    <div className="mb-3 flex items-center justify-between gap-3">
                        <div>
                            <h2 className="font-semibold">
                                Documente salvate
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                {documents.length}{' '}
                                {documents.length === 1
                                    ? 'document'
                                    : 'documente'}
                            </p>
                        </div>
                    </div>

                    {documents.length === 0 ? (
                        <div className="rounded-xl border border-dashed p-8 text-center">
                            <FolderOpen className="mx-auto size-9 text-muted-foreground" />
                            <h3 className="mt-3 font-medium">
                                Niciun document încă
                            </h3>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Încarcă primul contract, act sau document al
                                proprietății.
                            </p>
                        </div>
                    ) : (
                        <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            {documents.map((document) => (
                                <article
                                    key={document.id}
                                    className="flex flex-col rounded-xl border bg-card p-4 shadow-sm"
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
                                                {document.category_label}
                                            </p>
                                        </div>
                                    </div>

                                    <dl className="mt-4 grid gap-2 text-sm">
                                        <div className="flex justify-between gap-3">
                                            <dt className="text-muted-foreground">
                                                Proprietate
                                            </dt>
                                            <dd className="text-right font-medium">
                                                {document.property
                                                    ? `${document.property.name} · ${document.property.city}`
                                                    : 'Proprietate ștearsă'}
                                            </dd>
                                        </div>
                                        {document.lease ? (
                                            <div className="flex justify-between gap-3">
                                                <dt className="text-muted-foreground">
                                                    Contract
                                                </dt>
                                                <dd className="text-right font-medium">
                                                    {document.lease.renter_name}
                                                </dd>
                                            </div>
                                        ) : null}
                                        <div className="flex justify-between gap-3">
                                            <dt className="text-muted-foreground">
                                                Data
                                            </dt>
                                            <dd className="text-right">
                                                {formatDate(
                                                    document.document_date,
                                                )}
                                            </dd>
                                        </div>
                                        {document.expires_on ? (
                                            <div className="flex justify-between gap-3">
                                                <dt className="text-muted-foreground">
                                                    Expiră
                                                </dt>
                                                <dd className="text-right">
                                                    {formatDate(
                                                        document.expires_on,
                                                    )}
                                                </dd>
                                            </div>
                                        ) : null}
                                        <div className="flex justify-between gap-3">
                                            <dt className="text-muted-foreground">
                                                Dimensiune
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
                                                Descarcă
                                            </a>
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            type="button"
                                            aria-label="Șterge documentul"
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
            title: 'Documente',
            href: props.currentTeam ? index(props.currentTeam.slug) : '/',
        },
    ],
});
