import { ShieldCheck } from 'lucide-react';
import { LocaleSwitcher } from '@/components/locale-switcher';
import { ThemeToggle } from '@/components/theme-toggle';
import { useI18n } from '@/lib/i18n';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { t } = useI18n();

    return (
        <div className="relative min-h-svh overflow-hidden bg-[#07111f] dark:bg-[#06101d]">
            <div
                className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_15%_15%,rgba(52,211,153,0.12),transparent_28%),radial-gradient(circle_at_80%_0%,rgba(59,130,246,0.10),transparent_30%)]"
                aria-hidden="true"
            />

            <div className="absolute top-4 right-4 z-20 flex items-center gap-2 sm:top-6 sm:right-6">
                <LocaleSwitcher className="border-white/10 bg-white/90 dark:bg-white/5 dark:text-slate-300" />
                <ThemeToggle className="border-white/10 bg-white/90 dark:bg-white/5" />
            </div>

            <div className="relative mx-auto grid min-h-svh w-full max-w-6xl items-center gap-10 px-5 py-16 sm:px-8 lg:grid-cols-[1.05fr_0.95fr] lg:px-10">
                <section className="hidden max-w-xl text-slate-100 lg:block">
                    <div className="inline-flex items-center gap-3">
                        <span className="flex size-11 items-center justify-center rounded-2xl bg-emerald-300 text-lg font-black text-[#07111f] shadow-lg shadow-emerald-400/20">
                            R
                        </span>
                        <div>
                            <p className="text-xl font-semibold tracking-tight">Rentier</p>
                            <p className="text-xs uppercase tracking-[0.18em] text-slate-400">
                                {t('app.tagline')}
                            </p>
                        </div>
                    </div>

                    <h2 className="mt-10 text-4xl font-semibold tracking-[-0.04em] text-balance">
                        {t('auth.shell.heroTitle')}
                    </h2>
                    <p className="mt-4 max-w-lg text-base leading-7 text-slate-400">
                        {t('auth.shell.heroDescription')}
                    </p>

                    <div className="mt-8 grid gap-3 text-sm text-slate-300">
                        {[
                            t('auth.shell.bulletIncome'),
                            t('auth.shell.bulletFlow'),
                            t('auth.shell.bulletCosts'),
                        ].map((item) => (
                            <div key={item} className="flex items-center gap-3">
                                <span className="flex size-8 items-center justify-center rounded-xl bg-emerald-300/10 text-emerald-300">
                                    <ShieldCheck className="size-4" aria-hidden="true" />
                                </span>
                                {item}
                            </div>
                        ))}
                    </div>
                </section>

                <main className="mx-auto w-full max-w-md">
                    <div className="rounded-[1.75rem] border border-slate-200/80 bg-white/95 p-6 shadow-2xl shadow-slate-950/10 backdrop-blur sm:p-8 dark:border-white/10 dark:bg-[#0b1420]/95 dark:shadow-black/30">
                        <div className="mb-7">
                            <div className="mb-5 flex items-center gap-3 lg:hidden">
                                <span className="flex size-10 items-center justify-center rounded-xl bg-emerald-300 text-sm font-black text-[#07111f]">R</span>
                                <div>
                                    <p className="font-semibold tracking-tight">Rentier</p>
                                    <p className="text-[10px] uppercase tracking-[0.16em] text-muted-foreground">{t('app.tagline')}</p>
                                </div>
                            </div>

                            <h1 className="text-2xl font-semibold tracking-[-0.03em]">{title}</h1>
                            <p className="mt-2 text-sm leading-6 text-muted-foreground">{description}</p>
                        </div>

                        {children}
                    </div>

                    <p className="mt-5 text-center text-xs text-slate-500 dark:text-slate-500">
                        Rentier · {t('header.privateBeta').toLowerCase()}
                    </p>
                </main>
            </div>
        </div>
    );
}
