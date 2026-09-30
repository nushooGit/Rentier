import AuthLayoutTemplate from '@/layouts/auth/auth-simple-layout';
import { useI18n } from '@/lib/i18n';

export default function AuthLayout({
    title = '',
    description = '',
    children,
}: {
    title?: string;
    description?: string;
    children: React.ReactNode;
}) {
    const { translate } = useI18n();

    return (
        <AuthLayoutTemplate title={translate(title)} description={translate(description)}>
            {children}
        </AuthLayoutTemplate>
    );
}
