import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    CalendarClock,
    Download,
    Pencil,
    Plus,
    ReceiptText,
    Trash2,
    Upload,
    Zap,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import type { FormEvent } from 'react';
import DateInput from '@/components/date-input';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateLong } from '@/lib/date';
import { translateKey, useI18n } from '@/lib/i18n';
import { formatMoney } from '@/lib/money';
import { download as downloadDocument } from '@/routes/documents';
import {
    destroy as destroyUtilityAccount,
    store as storeUtilityAccount,
    update as updateUtilityAccount,
} from '@/routes/utility-accounts';
import {
    destroy as destroyUtilityBill,
    store as storeUtilityBill,
    update as updateUtilityBill,
} from '@/routes/utility-bills';
import { index as utilitiesIndex } from '@/routes/utilities';
import type {
    UtilityAccountItem,
    UtilityBillItem,
    UtilityLeaseOption,
    UtilityPropertyOption,
    UtilityServiceType,
    UtilitySummary,
} from '@/types';

type Props = {
    accounts: UtilityAccountItem[];
    bills: UtilityBillItem[];
    properties: UtilityPropertyOption[];
    leases: UtilityLeaseOption[];
    summary: UtilitySummary;
};

type AccountFormData = {
    property_id: string;
    lease_id: string;
    provider_name: string;
    service_type: UtilityServiceType;
    account_identifier: string;
    responsible_party: 'owner' | 'renter';
    status: 'active' | 'inactive';
    notes: string;
};

type BillFormData = {
    utility_account_id: string;
    invoice_number: string;
    billing_period_start: string;
    billing_period_end: string;
    issue_date: string;
    due_date: string;
    amount: string;
    currency: string;
    status: 'unpaid' | 'paid';
    paid_on: string;
    notes: string;
    attachment: File | null;
};

const selectClassName =
    'border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-xl border px-3 py-1 text-sm shadow-xs transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50';

const serviceTypes: UtilityServiceType[] = [
    'electricity',
    'gas',
    'water',
    'heating',
    'internet',
    'sanitation',
    'other',
];

function localToday(): string {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${day}`;
}

function localMonthStart(): string {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`;
}

function serviceLabel(
    service: UtilityServiceType,
    t: ReturnType<typeof useI18n>['t'],
) {
    const keys: Record<
        UtilityServiceType,
        Parameters<typeof t>[0]
    > = {
        electricity: 'utilities.service.electricity',
        gas: 'utilities.service.gas',
        water: 'utilities.service.water',
        heating: 'utilities.service.heating',
        internet: 'utilities.service.internet',
        sanitation: 'utilities.service.sanitation',
        other: 'utilities.service.other',
    };

    return t(keys[service]);
}

function AccountDialog({
    open,
    onOpenChange,
    account,
    properties,
    leases,
    teamSlug,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    account: UtilityAccountItem | null;
    properties: UtilityPropertyOption[];
    leases: UtilityLeaseOption[];
    teamSlug: string;
}) {
    const { t } = useI18n();
    const form = useForm<AccountFormData>({
        property_id: account?.property_id.toString() ?? '',
        lease_id: account?.lease_id?.toString() ?? '',
        provider_name: account?.provider_name ?? '',
        service_type: account?.service_type ?? 'electricity',
        account_identifier: account?.account_identifier ?? '',
        responsible_party: account?.responsible_party ?? 'owner',
        status: account?.status ?? 'active',
        notes: account?.notes ?? '',
    });

    const propertyLeases = useMemo(() => {
        if (form.data.property_id === '') {
            return [];
        }

        return leases.filter(
            (lease) =>
                lease.property_id.toString() === form.data.property_id,
        );
    }, [form.data.property_id, leases]);

    const close = () => {
        form.clearErrors();
        form.reset();
        onOpenChange(false);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: close,
        };

        if (account) {
            form.put(updateUtilityAccount([teamSlug, account.id]).url, options);

            return;
        }

        form.post(storeUtilityAccount(teamSlug).url, options);
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(nextOpen) => {
                if (!nextOpen) {
                    close();

                    return;
                }

                onOpenChange(true);
            }}
        >
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>
                        {account
                            ? t('utilities.accounts.edit')
                            : t('utilities.accounts.new')}
                    </DialogTitle>
                    <DialogDescription>
                        {t('utilities.accountFormDescription')}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="grid gap-1.5">
                            <Label htmlFor="utility-property">
                                {t('utilities.property')}
                            </Label>
                            <select
                                id="utility-property"
                                value={form.data.property_id}
                                onChange={(event) => {
                                    const propertyId = event.target.value;
                                    const lease = leases.find(
                                        (item) =>
                                            item.id.toString() ===
                                            form.data.lease_id,
                                    );

                                    form.setData('property_id', propertyId);

                                    if (
                                        lease &&
                                        lease.property_id.toString() !==
                                            propertyId
                                    ) {
                                        form.setData('lease_id', '');
                                    }
                                }}
                                className={selectClassName}
                                required
                                data-test="utility-account-property-select"
                            >
                                <option value="">
                                    {t('utilities.chooseProperty')}
                                </option>
                                {properties.map((property) => (
                                    <option
                                        key={property.id}
                                        value={property.id}
                                    >
                                        {property.name}
                                        {property.city
                                            ? ` · ${property.city}`
                                            : ''}
                                    </option>
                                ))}
                            </select>
                            <InputError message={form.errors.property_id} />
                        </div>

                        <div className="grid gap-1.5">
                            <Label htmlFor="utility-lease">
                                {t('utilities.lease')}
                            </Label>
                            <select
                                id="utility-lease"
                                value={form.data.lease_id}
                                onChange={(event) =>
                                    form.setData('lease_id', event.target.value)
                                }
                                className={selectClassName}
                                required={
                                    form.data.responsible_party === 'renter'
                                }
                                data-test="utility-account-lease-select"
                            >
                                <option value="">
                                    {t('utilities.noLease')}
                                </option>
                                {propertyLeases.map((lease) => (
                                    <option key={lease.id} value={lease.id}>
                                        {lease.label}
                                    </option>
                                ))}
                            </select>
                            <InputError message={form.errors.lease_id} />
                            {form.data.responsible_party === 'renter' ? (
                                <p className="text-xs text-muted-foreground">
                                    {t('utilities.renterNeedsLease')}
                                </p>
                            ) : null}
                        </div>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="grid gap-1.5">
                            <Label htmlFor="utility-provider">
                                {t('utilities.provider')}
                            </Label>
                            <Input
                                id="utility-provider"
                                value={form.data.provider_name}
                                onChange={(event) =>
                                    form.setData(
                                        'provider_name',
                                        event.target.value,
                                    )
                                }
                                maxLength={191}
                                required
                                data-test="utility-provider-input"
                            />
                            <InputError message={form.errors.provider_name} />
                        </div>

                        <div className="grid gap-1.5">
                            <Label htmlFor="utility-service">
                                {t('utilities.service')}
                            </Label>
                            <select
                                id="utility-service"
                                value={form.data.service_type}
                                onChange={(event) =>
                                    form.setData(
                                        'service_type',
                                        event.target
                                            .value as UtilityServiceType,
                                    )
                                }
                                className={selectClassName}
                                data-test="utility-service-select"
                            >
                                {serviceTypes.map((service) => (
                                    <option key={service} value={service}>
                                        {serviceLabel(service, t)}
                                    </option>
                                ))}
                            </select>
                            <InputError message={form.errors.service_type} />
                        </div>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-3">
                        <div className="grid gap-1.5 sm:col-span-1">
                            <Label htmlFor="utility-identifier">
                                {t('utilities.accountIdentifier')}
                            </Label>
                            <Input
                                id="utility-identifier"
                                value={form.data.account_identifier}
                                onChange={(event) =>
                                    form.setData(
                                        'account_identifier',
                                        event.target.value,
                                    )
                                }
                                maxLength={191}
                                data-test="utility-account-identifier-input"
                            />
                            <InputError
                                message={form.errors.account_identifier}
                            />
                        </div>

                        <div className="grid gap-1.5">
                            <Label htmlFor="utility-responsibility">
                                {t('utilities.responsibility')}
                            </Label>
                            <select
                                id="utility-responsibility"
                                value={form.data.responsible_party}
                                onChange={(event) =>
                                    form.setData(
                                        'responsible_party',
                                        event.target.value as
                                            | 'owner'
                                            | 'renter',
                                    )
                                }
                                className={selectClassName}
                                data-test="utility-responsibility-select"
                            >
                                <option value="owner">
                                    {t('utilities.owner')}
                                </option>
                                <option value="renter">
                                    {t('utilities.renter')}
                                </option>
                            </select>
                            <InputError
                                message={form.errors.responsible_party}
                            />
                        </div>

                        <div className="grid gap-1.5">
                            <Label htmlFor="utility-status">
                                {t('utilities.accountStatus')}
                            </Label>
                            <select
                                id="utility-status"
                                value={form.data.status}
                                onChange={(event) =>
                                    form.setData(
                                        'status',
                                        event.target.value as
                                            | 'active'
                                            | 'inactive',
                                    )
                                }
                                className={selectClassName}
                            >
                                <option value="active">
                                    {t('utilities.active')}
                                </option>
                                <option value="inactive">
                                    {t('utilities.inactive')}
                                </option>
                            </select>
                            <InputError message={form.errors.status} />
                        </div>
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="utility-account-notes">
                            {t('utilities.notes')}
                        </Label>
                        <textarea
                            id="utility-account-notes"
                            value={form.data.notes}
                            onChange={(event) =>
                                form.setData('notes', event.target.value)
                            }
                            rows={3}
                            maxLength={5000}
                            className="border-input bg-background ring-offset-background focus-visible:ring-ring min-h-20 w-full resize-y rounded-xl border px-3 py-2 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                        />
                        <InputError message={form.errors.notes} />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={close}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button
                            type="submit"
                            disabled={form.processing}
                            data-test="utility-account-save-button"
                        >
                            {t('utilities.saveAccount')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function BillDialog({
    open,
    onOpenChange,
    bill,
    accounts,
    teamSlug,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    bill: UtilityBillItem | null;
    accounts: UtilityAccountItem[];
    teamSlug: string;
}) {
    const { locale, t } = useI18n();
    const today = localToday();
    const form = useForm<BillFormData>({
        utility_account_id: bill?.utility_account_id.toString() ?? '',
        invoice_number: bill?.invoice_number ?? '',
        billing_period_start:
            bill?.billing_period_start ?? localMonthStart(),
        billing_period_end: bill?.billing_period_end ?? today,
        issue_date: bill?.issue_date ?? today,
        due_date: bill?.due_date ?? today,
        amount: bill?.amount ?? '',
        currency: bill?.currency ?? 'RON',
        status: bill?.status ?? 'unpaid',
        paid_on: bill?.paid_on ?? '',
        notes: bill?.notes ?? '',
        attachment: null,
    });
    const [fileName, setFileName] = useState<string | null>(null);

    const close = () => {
        form.clearErrors();
        form.reset();
        setFileName(null);
        onOpenChange(false);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: close,
        };

        if (bill) {
            form.put(updateUtilityBill([teamSlug, bill.id]).url, options);

            return;
        }

        form.post(storeUtilityBill(teamSlug).url, {
            ...options,
            forceFormData: true,
        });
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(nextOpen) => {
                if (!nextOpen) {
                    close();

                    return;
                }

                onOpenChange(true);
            }}
        >
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>
                        {bill
                            ? t('utilities.bills.edit')
                            : t('utilities.bills.new')}
                    </DialogTitle>
                    <DialogDescription>
                        {t('utilities.billFormDescription')}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-1.5">
                        <Label htmlFor="utility-bill-account">
                            {t('utilities.chooseAccount')}
                        </Label>
                        <select
                            id="utility-bill-account"
                            value={form.data.utility_account_id}
                            onChange={(event) =>
                                form.setData(
                                    'utility_account_id',
                                    event.target.value,
                                )
                            }
                            className={selectClassName}
                            required
                            data-test="utility-bill-account-select"
                        >
                            <option value="">
                                {t('utilities.chooseAccount')}
                            </option>
                            {accounts.map((account) => (
                                <option key={account.id} value={account.id}>
                                    {account.provider_name} ·{' '}
                                    {serviceLabel(account.service_type, t)} ·{' '}
                                    {account.property.name}
                                </option>
                            ))}
                        </select>
                        <InputError
                            message={form.errors.utility_account_id}
                        />
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="grid gap-1.5">
                            <Label htmlFor="utility-invoice-number">
                                {t('utilities.invoiceNumber')}
                            </Label>
                            <Input
                                id="utility-invoice-number"
                                value={form.data.invoice_number}
                                onChange={(event) =>
                                    form.setData(
                                        'invoice_number',
                                        event.target.value,
                                    )
                                }
                                maxLength={191}
                                required
                                data-test="utility-bill-number-input"
                            />
                            <InputError
                                message={form.errors.invoice_number}
                            />
                        </div>

                        <div className="grid gap-1.5">
                            <Label htmlFor="utility-amount">
                                {t('utilities.amount')}
                            </Label>
                            <div className="grid grid-cols-[1fr_100px] gap-2">
                                <Input
                                    id="utility-amount"
                                    inputMode="decimal"
                                    value={form.data.amount}
                                    onChange={(event) =>
                                        form.setData(
                                            'amount',
                                            event.target.value,
                                        )
                                    }
                                    required
                                    data-test="utility-bill-amount-input"
                                />
                                <select
                                    value={form.data.currency}
                                    onChange={(event) =>
                                        form.setData(
                                            'currency',
                                            event.target.value,
                                        )
                                    }
                                    className={selectClassName}
                                    aria-label={t('utilities.currency')}
                                >
                                    <option value="RON">RON</option>
                                    <option value="EUR">EUR</option>
                                </select>
                            </div>
                            <InputError message={form.errors.amount} />
                            <InputError message={form.errors.currency} />
                        </div>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="grid gap-1.5">
                            <Label>{t('utilities.billingPeriodStart')}</Label>
                            <DateInput
                                key={`start-${bill?.id ?? 'new'}`}
                                name="billing_period_start"
                                defaultValue={form.data.billing_period_start}
                                locale={locale === 'ro' ? 'ro-RO' : 'en-US'}
                                onValueChange={(value) =>
                                    form.setData(
                                        'billing_period_start',
                                        value,
                                    )
                                }
                                error={form.errors.billing_period_start}
                                required
                                data-test="utility-billing-start-input"
                            />
                        </div>

                        <div className="grid gap-1.5">
                            <Label>{t('utilities.billingPeriodEnd')}</Label>
                            <DateInput
                                key={`end-${bill?.id ?? 'new'}`}
                                name="billing_period_end"
                                defaultValue={form.data.billing_period_end}
                                locale={locale === 'ro' ? 'ro-RO' : 'en-US'}
                                onValueChange={(value) =>
                                    form.setData('billing_period_end', value)
                                }
                                error={form.errors.billing_period_end}
                                required
                                data-test="utility-billing-end-input"
                            />
                        </div>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="grid gap-1.5">
                            <Label>{t('utilities.issueDate')}</Label>
                            <DateInput
                                key={`issue-${bill?.id ?? 'new'}`}
                                name="issue_date"
                                defaultValue={form.data.issue_date}
                                locale={locale === 'ro' ? 'ro-RO' : 'en-US'}
                                onValueChange={(value) =>
                                    form.setData('issue_date', value)
                                }
                                error={form.errors.issue_date}
                                required
                                data-test="utility-issue-date-input"
                            />
                        </div>

                        <div className="grid gap-1.5">
                            <Label>{t('utilities.dueDate')}</Label>
                            <DateInput
                                key={`due-${bill?.id ?? 'new'}`}
                                name="due_date"
                                defaultValue={form.data.due_date}
                                locale={locale === 'ro' ? 'ro-RO' : 'en-US'}
                                onValueChange={(value) =>
                                    form.setData('due_date', value)
                                }
                                error={form.errors.due_date}
                                required
                                data-test="utility-due-date-input"
                            />
                        </div>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="grid gap-1.5">
                            <Label htmlFor="utility-bill-status">
                                {t('utilities.billStatus')}
                            </Label>
                            <select
                                id="utility-bill-status"
                                value={form.data.status}
                                onChange={(event) => {
                                    const status = event.target.value as
                                        | 'unpaid'
                                        | 'paid';
                                    form.setData('status', status);

                                    if (
                                        status === 'paid' &&
                                        form.data.paid_on === ''
                                    ) {
                                        form.setData('paid_on', today);
                                    }

                                    if (status === 'unpaid') {
                                        form.setData('paid_on', '');
                                    }
                                }}
                                className={selectClassName}
                                data-test="utility-bill-status-select"
                            >
                                <option value="unpaid">
                                    {t('utilities.unpaid')}
                                </option>
                                <option value="paid">
                                    {t('utilities.paid')}
                                </option>
                            </select>
                            <InputError message={form.errors.status} />
                        </div>

                        <div className="grid gap-1.5">
                            <Label>{t('utilities.paidOn')}</Label>
                            <DateInput
                                key={`paid-${bill?.id ?? 'new'}-${form.data.status}`}
                                name="paid_on"
                                defaultValue={form.data.paid_on}
                                locale={locale === 'ro' ? 'ro-RO' : 'en-US'}
                                onValueChange={(value) =>
                                    form.setData('paid_on', value)
                                }
                                error={form.errors.paid_on}
                                disabled={form.data.status !== 'paid'}
                                required={form.data.status === 'paid'}
                                data-test="utility-paid-on-input"
                            />
                        </div>
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="utility-bill-notes">
                            {t('utilities.notes')}
                        </Label>
                        <textarea
                            id="utility-bill-notes"
                            value={form.data.notes}
                            onChange={(event) =>
                                form.setData('notes', event.target.value)
                            }
                            rows={3}
                            maxLength={5000}
                            className="border-input bg-background ring-offset-background focus-visible:ring-ring min-h-20 w-full resize-y rounded-xl border px-3 py-2 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                        />
                        <InputError message={form.errors.notes} />
                    </div>

                    {!bill ? (
                        <div className="grid gap-1.5">
                            <Label htmlFor="utility-attachment">
                                {t('utilities.attachment')}
                            </Label>
                            <label
                                htmlFor="utility-attachment"
                                className="flex cursor-pointer items-center gap-3 rounded-2xl border border-dashed border-border bg-muted/20 px-4 py-4 transition hover:bg-muted/35"
                            >
                                <span className="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                    <Upload className="size-5" />
                                </span>
                                <span className="min-w-0">
                                    <span className="block truncate text-sm font-medium">
                                        {fileName ??
                                            t('utilities.attachment')}
                                    </span>
                                    <span className="block text-xs text-muted-foreground">
                                        {t('utilities.attachmentHelp')}
                                    </span>
                                </span>
                            </label>
                            <input
                                id="utility-attachment"
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png,.webp"
                                className="sr-only"
                                onChange={(event) => {
                                    const file =
                                        event.currentTarget.files?.[0] ?? null;
                                    form.setData('attachment', file);
                                    setFileName(file?.name ?? null);
                                }}
                                data-test="utility-attachment-input"
                            />
                            <InputError message={form.errors.attachment} />
                        </div>
                    ) : bill.document ? (
                        <p className="text-xs text-muted-foreground">
                            {t('utilities.replaceAttachmentHelp')}
                        </p>
                    ) : null}

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={close}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button
                            type="submit"
                            disabled={form.processing}
                            data-test="utility-bill-save-button"
                        >
                            {t('utilities.saveBill')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function UtilitiesIndex({
    accounts,
    bills,
    properties,
    leases,
    summary,
}: Props) {
    const page = usePage();
    const { t } = useI18n();
    const teamSlug = page.props.currentTeam?.slug ?? '';
    const errors = page.props.errors as Record<string, string> | undefined;
    const [accountDialogOpen, setAccountDialogOpen] = useState(false);
    const [billDialogOpen, setBillDialogOpen] = useState(false);
    const [editingAccount, setEditingAccount] =
        useState<UtilityAccountItem | null>(null);
    const [editingBill, setEditingBill] =
        useState<UtilityBillItem | null>(null);

    const openNewAccount = () => {
        setEditingAccount(null);
        setAccountDialogOpen(true);
    };

    const openAccount = (account: UtilityAccountItem) => {
        setEditingAccount(account);
        setAccountDialogOpen(true);
    };

    const openNewBill = () => {
        setEditingBill(null);
        setBillDialogOpen(true);
    };

    const openBill = (bill: UtilityBillItem) => {
        setEditingBill(bill);
        setBillDialogOpen(true);
    };

    const deleteAccount = (account: UtilityAccountItem) => {
        if (account.bill_count > 0) {
            window.alert(t('utilities.accountHasBills'));

            return;
        }

        if (!window.confirm(t('utilities.deleteAccountConfirm'))) {
            return;
        }

        router.delete(
            destroyUtilityAccount([teamSlug, account.id]).url,
            { preserveScroll: true },
        );
    };

    const deleteBill = (bill: UtilityBillItem) => {
        if (
            !window.confirm(
                t('utilities.deleteBillConfirm', {
                    number: bill.invoice_number,
                }),
            )
        ) {
            return;
        }

        router.delete(destroyUtilityBill([teamSlug, bill.id]).url, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title={t('nav.utilities')} />

            <div className="mx-auto flex w-full max-w-[1480px] flex-col gap-5 p-3 sm:p-5 lg:p-6">
                <div className="flex flex-col gap-4 rounded-2xl border border-border/70 bg-card/75 p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
                    <Heading
                        variant="small"
                        title={t('nav.utilities')}
                        description={t('utilities.index.description')}
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={openNewAccount}
                            disabled={properties.length === 0}
                            data-test="utility-add-account-button"
                        >
                            <Plus />
                            {t('utilities.accounts.new')}
                        </Button>
                        <Button
                            type="button"
                            onClick={openNewBill}
                            disabled={accounts.length === 0}
                            data-test="utility-add-bill-button"
                        >
                            <ReceiptText />
                            {t('utilities.bills.new')}
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-2.5 lg:grid-cols-4">
                    {[
                        [
                            t('utilities.summary.activeAccounts'),
                            summary.active_accounts,
                        ],
                        [
                            t('utilities.summary.unpaidBills'),
                            summary.unpaid_bills,
                        ],
                        [
                            t('utilities.summary.overdueBills'),
                            summary.overdue_bills,
                        ],
                        [
                            t('utilities.summary.attachments'),
                            summary.attached_bills,
                        ],
                    ].map(([label, value]) => (
                        <section
                            key={String(label)}
                            className="rounded-2xl border border-border/70 bg-card p-4 shadow-sm"
                        >
                            <p className="text-xs font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                                {label}
                            </p>
                            <p className="mt-2 text-2xl font-semibold tracking-tight">
                                {value}
                            </p>
                        </section>
                    ))}
                </div>

                {errors?.utility_account ? (
                    <div className="rounded-xl border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive">
                        {errors.utility_account}
                    </div>
                ) : null}

                <section className="rounded-2xl border border-border/70 bg-card/90 p-4 shadow-sm sm:p-5">
                    <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 className="text-base font-semibold">
                                {t('utilities.accounts.title')}
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {t('utilities.accounts.description')}
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={openNewAccount}
                            disabled={properties.length === 0}
                        >
                            <Plus />
                            {t('utilities.accounts.new')}
                        </Button>
                    </div>

                    {accounts.length === 0 ? (
                        <div className="rounded-2xl border border-dashed border-border bg-background/50 p-8 text-center">
                            <Zap className="mx-auto size-8 text-muted-foreground" />
                            <h3 className="mt-3 text-sm font-semibold">
                                {t('utilities.accounts.emptyTitle')}
                            </h3>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {t('utilities.accounts.emptyDescription')}
                            </p>
                        </div>
                    ) : (
                        <div className="grid gap-3 lg:grid-cols-2">
                            {accounts.map((account) => (
                                <article
                                    key={account.id}
                                    className="rounded-2xl border border-border/70 bg-background/55 p-4"
                                    data-test="utility-account-card"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h3 className="truncate font-semibold">
                                                    {account.provider_name}
                                                </h3>
                                                <Badge
                                                    variant="outline"
                                                    className={
                                                        account.status ===
                                                        'active'
                                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-200'
                                                            : ''
                                                    }
                                                >
                                                    {account.status ===
                                                    'active'
                                                        ? t('utilities.active')
                                                        : t(
                                                              'utilities.inactive',
                                                          )}
                                                </Badge>
                                            </div>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {serviceLabel(
                                                    account.service_type,
                                                    t,
                                                )}{' '}
                                                · {account.property.name}
                                            </p>
                                        </div>

                                        <div className="flex shrink-0 gap-1">
                                            <Button
                                                type="button"
                                                size="icon-sm"
                                                variant="ghost"
                                                onClick={() =>
                                                    openAccount(account)
                                                }
                                                aria-label={t(
                                                    'utilities.accounts.edit',
                                                )}
                                            >
                                                <Pencil />
                                            </Button>
                                            <Button
                                                type="button"
                                                size="icon-sm"
                                                variant="ghost"
                                                onClick={() =>
                                                    deleteAccount(account)
                                                }
                                                aria-label={t(
                                                    'utilities.deleteAccount',
                                                )}
                                            >
                                                <Trash2 />
                                            </Button>
                                        </div>
                                    </div>

                                    <dl className="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                                        <div>
                                            <dt className="text-xs text-muted-foreground">
                                                {t(
                                                    'utilities.accountIdentifier',
                                                )}
                                            </dt>
                                            <dd className="mt-0.5 font-medium">
                                                {account.account_identifier ??
                                                    '—'}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-xs text-muted-foreground">
                                                {t(
                                                    'utilities.responsibility',
                                                )}
                                            </dt>
                                            <dd className="mt-0.5 font-medium">
                                                {account.responsible_party ===
                                                'owner'
                                                    ? t('utilities.owner')
                                                    : `${t('utilities.renter')}${account.renter_name ? ` · ${account.renter_name}` : ''}`}
                                            </dd>
                                        </div>
                                    </dl>

                                    {account.notes ? (
                                        <p className="mt-3 text-xs text-muted-foreground">
                                            {account.notes}
                                        </p>
                                    ) : null}
                                </article>
                            ))}
                        </div>
                    )}
                </section>

                <section className="rounded-2xl border border-border/70 bg-card/90 p-4 shadow-sm sm:p-5">
                    <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 className="text-base font-semibold">
                                {t('utilities.bills.title')}
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {t('utilities.bills.description')}
                            </p>
                        </div>
                        <Button
                            type="button"
                            size="sm"
                            onClick={openNewBill}
                            disabled={accounts.length === 0}
                        >
                            <Plus />
                            {t('utilities.bills.new')}
                        </Button>
                    </div>

                    {bills.length === 0 ? (
                        <div className="rounded-2xl border border-dashed border-border bg-background/50 p-8 text-center">
                            <ReceiptText className="mx-auto size-8 text-muted-foreground" />
                            <h3 className="mt-3 text-sm font-semibold">
                                {t('utilities.bills.emptyTitle')}
                            </h3>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {t('utilities.bills.emptyDescription')}
                            </p>
                        </div>
                    ) : (
                        <div className="space-y-3">
                            {bills.map((bill) => (
                                <article
                                    key={bill.id}
                                    className="rounded-2xl border border-border/70 bg-background/55 p-4"
                                    data-test="utility-bill-card"
                                >
                                    <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                        <div className="min-w-0">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h3 className="font-semibold">
                                                    {
                                                        bill.account
                                                            .provider_name
                                                    }{' '}
                                                    · {bill.invoice_number}
                                                </h3>
                                                <Badge
                                                    variant="outline"
                                                    className={
                                                        bill.overdue
                                                            ? 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-400/20 dark:bg-rose-400/10 dark:text-rose-200'
                                                            : bill.status ===
                                                                'paid'
                                                              ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-200'
                                                              : ''
                                                    }
                                                >
                                                    {bill.overdue
                                                        ? t(
                                                              'utilities.overdue',
                                                          )
                                                        : bill.status ===
                                                            'paid'
                                                          ? t(
                                                                'utilities.paid',
                                                            )
                                                          : t(
                                                                'utilities.unpaid',
                                                            )}
                                                </Badge>
                                            </div>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {serviceLabel(
                                                    bill.account.service_type,
                                                    t,
                                                )}{' '}
                                                · {bill.property.name}
                                                {bill.renter_name
                                                    ? ` · ${bill.renter_name}`
                                                    : ''}
                                            </p>
                                        </div>

                                        <div className="flex shrink-0 flex-wrap items-center gap-2">
                                            {bill.document ? (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={
                                                            downloadDocument([
                                                                teamSlug,
                                                                bill.document
                                                                    .id,
                                                            ]).url
                                                        }
                                                    >
                                                        <Download />
                                                        {t(
                                                            'utilities.downloadAttachment',
                                                        )}
                                                    </Link>
                                                </Button>
                                            ) : null}
                                            <Button
                                                type="button"
                                                size="icon-sm"
                                                variant="ghost"
                                                onClick={() => openBill(bill)}
                                                aria-label={t(
                                                    'utilities.bills.edit',
                                                )}
                                            >
                                                <Pencil />
                                            </Button>
                                            <Button
                                                type="button"
                                                size="icon-sm"
                                                variant="ghost"
                                                onClick={() =>
                                                    deleteBill(bill)
                                                }
                                                aria-label={t(
                                                    'utilities.deleteBill',
                                                )}
                                            >
                                                <Trash2 />
                                            </Button>
                                        </div>
                                    </div>

                                    <div className="mt-4 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                                        <div>
                                            <p className="text-xs text-muted-foreground">
                                                {t(
                                                    'utilities.billingPeriod',
                                                )}
                                            </p>
                                            <p className="mt-0.5 font-medium">
                                                {formatDateLong(
                                                    bill.billing_period_start,
                                                )}{' '}
                                                –{' '}
                                                {formatDateLong(
                                                    bill.billing_period_end,
                                                )}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-xs text-muted-foreground">
                                                {t('utilities.dueDate')}
                                            </p>
                                            <p className="mt-0.5 flex items-center gap-1.5 font-medium">
                                                <CalendarClock className="size-3.5 text-muted-foreground" />
                                                {formatDateLong(bill.due_date)}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-xs text-muted-foreground">
                                                {t('utilities.amount')}
                                            </p>
                                            <p className="mt-0.5 font-semibold">
                                                {formatMoney(
                                                    bill.amount,
                                                    bill.currency,
                                                )}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-xs text-muted-foreground">
                                                {t('utilities.attachment')}
                                            </p>
                                            <p className="mt-0.5 truncate font-medium">
                                                {bill.document?.original_name ??
                                                    '—'}
                                            </p>
                                        </div>
                                    </div>

                                    {bill.notes ? (
                                        <p className="mt-3 text-xs text-muted-foreground">
                                            {bill.notes}
                                        </p>
                                    ) : null}
                                </article>
                            ))}
                        </div>
                    )}
                </section>
            </div>

            <AccountDialog
                key={
                    editingAccount
                        ? `account-${editingAccount.id}`
                        : `account-new-${accountDialogOpen ? 'open' : 'closed'}`
                }
                open={accountDialogOpen}
                onOpenChange={setAccountDialogOpen}
                account={editingAccount}
                properties={properties}
                leases={leases}
                teamSlug={teamSlug}
            />

            <BillDialog
                key={
                    editingBill
                        ? `bill-${editingBill.id}`
                        : `bill-new-${billDialogOpen ? 'open' : 'closed'}`
                }
                open={billDialogOpen}
                onOpenChange={setBillDialogOpen}
                bill={editingBill}
                accounts={accounts}
                teamSlug={teamSlug}
            />
        </>
    );
}

UtilitiesIndex.layout = (props: {
    currentTeam?: { slug: string } | null;
}) => ({
    breadcrumbs: [
        {
            title: translateKey('nav.utilities'),
            href: props.currentTeam ? utilitiesIndex(props.currentTeam.slug) : '/',
        },
    ],
});
