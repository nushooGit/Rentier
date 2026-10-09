import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import PasswordRequirements from '@/components/password-requirements';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/lib/i18n';

type Props = {
    token: string;
    email: string;
    authenticated: boolean;
    verified: boolean;
    matchesEmail: boolean;
};

export default function RenterInvitationShow({
    token,
    email,
    authenticated,
    verified,
    matchesEmail,
}: Props) {
    const { t } = useI18n();
    const invitationUrl = `/renter-invitations/${encodeURIComponent(token)}`;

    return (
        <main className="mx-auto flex min-h-screen max-w-md flex-col justify-center gap-6 p-6">
            <Head title={t('renter.invite.head')} />
            <h1 className="text-2xl font-semibold">{t('renter.invite.title')}</h1>
            <p>{t('renter.invite.description', { email })}</p>

            {!authenticated ? (
                <>
                    <p>
                        {t('renter.invite.existing')}{' '}
                        <a className="underline" href="/login">
                            {t('renter.invite.login')}
                        </a>
                        . {t('renter.invite.return')}
                    </p>
                    <Form
                        action={`${invitationUrl}/register`}
                        method="post"
                        className="space-y-4"
                    >
                        {({ errors, processing }) => (
                            <>
                                <div>
                                    <Label htmlFor="name">{t('renter.invite.name')}</Label>
                                    <Input id="name" name="name" required />
                                    <InputError message={errors.name} />
                                </div>
                                <div>
                                    <Label htmlFor="email">{t('renter.invite.email')}</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        name="email"
                                        value={email}
                                        readOnly
                                    />
                                    <InputError message={errors.email} />
                                </div>
                                <div>
                                    <Label htmlFor="password">{t('renter.invite.password')}</Label>
                                    <PasswordInput
                                        id="password"
                                        name="password"
                                        autoComplete="new-password"
                                        required
                                    />
                                    <PasswordRequirements />
                                    <InputError message={errors.password} />
                                </div>
                                <div>
                                    <Label htmlFor="password_confirmation">{t('renter.invite.confirm')}</Label>
                                    <PasswordInput
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        autoComplete="new-password"
                                        required
                                    />
                                    <InputError message={errors.password_confirmation} />
                                </div>
                                <Button type="submit" disabled={processing}>
                                    {t('renter.invite.create')}
                                </Button>
                            </>
                        )}
                    </Form>
                </>
            ) : !matchesEmail ? (
                <p>{t('renter.invite.mismatch', { email })}</p>
            ) : !verified ? (
                <p>{t('renter.invite.verify')}</p>
            ) : (
                <Form action="/renter-invitations/accept" method="post">
                    {({ errors, processing }) => (
                        <>
                            <input type="hidden" name="token" value={token} />
                            <InputError message={errors.invitation} />
                            <Button type="submit" disabled={processing}>
                                {t('renter.invite.accept')}
                            </Button>
                        </>
                    )}
                </Form>
            )}
        </main>
    );
}
