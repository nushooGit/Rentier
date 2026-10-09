import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    token: string;
    email: string;
    authenticated: boolean;
    verified: boolean;
    matchesEmail: boolean;
};

export default function RenterInvitationShow({ token, email, authenticated, verified, matchesEmail }: Props) {
    const invitationUrl = `/renter-invitations/${encodeURIComponent(token)}`;

    return (
        <main className="mx-auto flex min-h-screen max-w-md flex-col justify-center gap-6 p-6">
            <Head title="Invitație chiriaș · Rentier" />
            <h1 className="text-2xl font-semibold">Invitație Rentier</h1>
            <p>Ai fost invitat să îți accesezi informațiile de chiriaș, folosind adresa {email}.</p>
            {!authenticated ? (
                <>
                    <p>Ai deja cont? <a className="underline" href="/login">Autentifică-te</a>, apoi revino la linkul invitației.</p>
                    <Form action={`${invitationUrl}/register`} method="post" className="space-y-4">
                        {({ errors, processing }) => (
                            <>
                                <div><Label htmlFor="name">Nume complet</Label><Input id="name" name="name" required /><InputError message={errors.name} /></div>
                                <div><Label htmlFor="email">Email</Label><Input id="email" type="email" name="email" value={email} readOnly /><InputError message={errors.email} /></div>
                                <div><Label htmlFor="password">Parolă</Label><PasswordInput id="password" name="password" autoComplete="new-password" required /><InputError message={errors.password} /></div>
                                <div><Label htmlFor="password_confirmation">Confirmă parola</Label><PasswordInput id="password_confirmation" name="password_confirmation" autoComplete="new-password" required /></div>
                                <Button type="submit" disabled={processing}>Creează cont prin invitație</Button>
                            </>
                        )}
                    </Form>
                </>
            ) : !matchesEmail ? (
                <p>Contul autentificat folosește altă adresă. Ieși din cont și autentifică-te folosind {email}.</p>
            ) : !verified ? (
                <p>Verifică adresa de email folosind mesajul primit, apoi revino la această invitație.</p>
            ) : (
                <Form action="/renter-invitations/accept" method="post">
                    {({ errors, processing }) => (
                        <>
                            <input type="hidden" name="token" value={token} />
                            <InputError message={errors.invitation} />
                            <Button type="submit" disabled={processing}>Acceptă invitația</Button>
                        </>
                    )}
                </Form>
            )}
        </main>
    );
}
