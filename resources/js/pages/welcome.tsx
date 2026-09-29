import { Head, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Building2,
    CalendarDays,
    CheckCircle2,
    FileText,
    LayoutDashboard,
    ReceiptText,
    ShieldCheck,
    WalletCards,
} from 'lucide-react';

const features = [
    {
        icon: Building2,
        title: 'Proprietăți',
        description:
            'Ține la un loc informațiile esențiale despre fiecare proprietate, chiria lunară și suprafețele utile.',
    },
    {
        icon: FileText,
        title: 'Contracte',
        description:
            'Urmărește perioadele contractuale, chiria negociată, garanția și relația dintre proprietate și chiriaș.',
    },
    {
        icon: WalletCards,
        title: 'Plăți',
        description:
            'Înregistrează chirii și garanții, urmărește alocarea pe luni și vezi rapid ce este achitat sau restant.',
    },
    {
        icon: ReceiptText,
        title: 'Cheltuieli și decontări',
        description:
            'Notează cheltuielile, cine a plătit, cine le suportă și ce sume mai trebuie recuperate sau rambursate.',
    },
];

const highlights = [
    'Interfață în limba română',
    'Sume și evidențe în RON',
    'Gândit pentru proprietari cu una sau mai multe proprietăți',
];

export default function Welcome() {
    const { appUrl } = usePage().props;
    const loginUrl = `${appUrl.replace(/\/$/, '')}/login`;

    return (
        <>
            <Head title="Administrare chirii pentru proprietari">
                <meta
                    name="description"
                    content="Rentier te ajută să administrezi proprietăți, contracte, chirii, garanții și cheltuieli într-un singur loc."
                />
            </Head>

            <div className="min-h-screen bg-[#07111f] text-white">
                <header className="border-b border-white/10">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-5 py-5 sm:px-8 lg:px-10">
                        <a
                            href="#top"
                            className="flex items-center gap-3 font-semibold tracking-tight"
                            aria-label="Rentier - începutul paginii"
                        >
                            <span className="flex size-10 items-center justify-center rounded-xl bg-emerald-400 text-lg font-black text-[#07111f] shadow-lg shadow-emerald-400/20">
                                R
                            </span>
                            <span className="text-xl">Rentier</span>
                        </a>

                        <a
                            href={loginUrl}
                            className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300"
                        >
                            Intră în cont
                            <ArrowRight className="size-4" aria-hidden="true" />
                        </a>
                    </div>
                </header>

                <main id="top">
                    <section className="relative overflow-hidden">
                        <div
                            className="pointer-events-none absolute inset-x-0 top-0 h-[34rem] bg-[radial-gradient(circle_at_55%_20%,rgba(52,211,153,0.16),transparent_42%),radial-gradient(circle_at_10%_10%,rgba(59,130,246,0.12),transparent_30%)]"
                            aria-hidden="true"
                        />
                        <div className="relative mx-auto grid max-w-7xl gap-14 px-5 py-16 sm:px-8 sm:py-24 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:px-10 lg:py-28">
                            <div className="max-w-3xl">
                                <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-emerald-300/20 bg-emerald-300/10 px-3 py-1.5 text-sm font-medium text-emerald-200">
                                    <ShieldCheck
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                    Management locativ pentru proprietari
                                </div>

                                <h1 className="text-balance text-4xl font-semibold tracking-[-0.04em] sm:text-5xl lg:text-6xl">
                                    Ai grijă de proprietăți.
                                    <span className="block text-emerald-300">
                                        Rentier ține evidența.
                                    </span>
                                </h1>

                                <p className="mt-6 max-w-2xl text-pretty text-lg leading-8 text-slate-300 sm:text-xl">
                                    Contracte, chirii, garanții și cheltuieli
                                    într-un singur loc, construit pentru
                                    proprietarii din România.
                                </p>

                                <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                                    <a
                                        href={loginUrl}
                                        className="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-emerald-300 px-5 text-sm font-bold text-[#07111f] shadow-lg shadow-emerald-400/20 transition hover:bg-emerald-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-200 focus-visible:ring-offset-2 focus-visible:ring-offset-[#07111f]"
                                    >
                                        Intră în Rentier
                                        <ArrowRight
                                            className="size-4"
                                            aria-hidden="true"
                                        />
                                    </a>
                                    <a
                                        href="#functionalitati"
                                        className="inline-flex min-h-12 items-center justify-center rounded-xl border border-white/15 px-5 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                                    >
                                        Vezi ce poți urmări
                                    </a>
                                </div>

                                <div className="mt-8 flex flex-wrap gap-x-6 gap-y-3 text-sm text-slate-300">
                                    {highlights.map((highlight) => (
                                        <span
                                            key={highlight}
                                            className="inline-flex items-center gap-2"
                                        >
                                            <CheckCircle2
                                                className="size-4 text-emerald-300"
                                                aria-hidden="true"
                                            />
                                            {highlight}
                                        </span>
                                    ))}
                                </div>
                            </div>

                            <div className="relative">
                                <div
                                    className="absolute -inset-6 rounded-[2rem] bg-emerald-300/10 blur-3xl"
                                    aria-hidden="true"
                                />
                                <div className="relative overflow-hidden rounded-[1.75rem] border border-white/10 bg-[#0d1b2c] shadow-2xl shadow-black/30">
                                    <div className="flex items-center justify-between border-b border-white/10 px-5 py-4">
                                        <div>
                                            <p className="text-sm font-semibold">
                                                Imagine de ansamblu
                                            </p>
                                            <p className="mt-0.5 text-xs text-slate-400">
                                                Exemplu de organizare în Rentier
                                            </p>
                                        </div>
                                        <span className="rounded-full bg-emerald-300/10 px-2.5 py-1 text-xs font-medium text-emerald-200">
                                            Beta
                                        </span>
                                    </div>

                                    <div className="grid gap-3 p-4 sm:grid-cols-2 sm:p-5">
                                        <div className="rounded-2xl border border-white/10 bg-white/[0.035] p-4">
                                            <div className="flex items-center justify-between">
                                                <span className="text-xs uppercase tracking-[0.14em] text-slate-400">
                                                    Proprietăți
                                                </span>
                                                <Building2
                                                    className="size-4 text-emerald-300"
                                                    aria-hidden="true"
                                                />
                                            </div>
                                            <p className="mt-4 text-2xl font-semibold">
                                                Organizate
                                            </p>
                                            <p className="mt-1 text-sm text-slate-400">
                                                Date, chirie și contracte
                                            </p>
                                        </div>

                                        <div className="rounded-2xl border border-white/10 bg-white/[0.035] p-4">
                                            <div className="flex items-center justify-between">
                                                <span className="text-xs uppercase tracking-[0.14em] text-slate-400">
                                                    Plăți
                                                </span>
                                                <WalletCards
                                                    className="size-4 text-sky-300"
                                                    aria-hidden="true"
                                                />
                                            </div>
                                            <p className="mt-4 text-2xl font-semibold">
                                                La zi
                                            </p>
                                            <p className="mt-1 text-sm text-slate-400">
                                                Chirii, garanții și restanțe
                                            </p>
                                        </div>

                                        <div className="sm:col-span-2 rounded-2xl border border-white/10 bg-white/[0.035] p-4">
                                            <div className="flex items-center gap-3">
                                                <div className="flex size-10 items-center justify-center rounded-xl bg-amber-300/10 text-amber-200">
                                                    <CalendarDays
                                                        className="size-5"
                                                        aria-hidden="true"
                                                    />
                                                </div>
                                                <div className="min-w-0">
                                                    <p className="font-medium">
                                                        Contracte și scadențe
                                                    </p>
                                                    <p className="mt-0.5 text-sm text-slate-400">
                                                        Vezi rapid ce urmează și
                                                        ce necesită atenție.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="sm:col-span-2 rounded-2xl border border-emerald-300/20 bg-emerald-300/[0.06] p-4">
                                            <div className="flex items-start gap-3">
                                                <LayoutDashboard
                                                    className="mt-0.5 size-5 shrink-0 text-emerald-300"
                                                    aria-hidden="true"
                                                />
                                                <p className="text-sm leading-6 text-slate-200">
                                                    Mai puține foi, mesaje și
                                                    calcule împrăștiate. O
                                                    evidență clară pentru
                                                    operațiunile de zi cu zi.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section
                        id="functionalitati"
                        className="border-y border-white/10 bg-white/[0.025]"
                    >
                        <div className="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-20 lg:px-10">
                            <div className="max-w-2xl">
                                <p className="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-300">
                                    Esențialul, într-un singur loc
                                </p>
                                <h2 className="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
                                    Urmărești ce contează, fără să reconstruiești
                                    situația din mai multe fișiere.
                                </h2>
                            </div>

                            <div className="mt-10 grid gap-4 md:grid-cols-2">
                                {features.map(
                                    ({ icon: Icon, title, description }) => (
                                        <article
                                            key={title}
                                            className="rounded-2xl border border-white/10 bg-[#0b1727] p-6"
                                        >
                                            <div className="flex size-11 items-center justify-center rounded-xl bg-emerald-300/10 text-emerald-300">
                                                <Icon
                                                    className="size-5"
                                                    aria-hidden="true"
                                                />
                                            </div>
                                            <h3 className="mt-5 text-xl font-semibold">
                                                {title}
                                            </h3>
                                            <p className="mt-2 leading-7 text-slate-400">
                                                {description}
                                            </p>
                                        </article>
                                    ),
                                )}
                            </div>
                        </div>
                    </section>

                    <section className="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-24 lg:px-10">
                        <div className="grid gap-10 rounded-[1.75rem] border border-white/10 bg-gradient-to-br from-[#0c1c2e] to-[#0a1524] p-6 sm:p-10 lg:grid-cols-[1fr_auto] lg:items-center">
                            <div>
                                <p className="text-sm font-semibold text-emerald-300">
                                    Rentier este în beta privată.
                                </p>
                                <h2 className="mt-2 text-3xl font-semibold tracking-tight">
                                    Ai deja acces? Continuă în aplicație.
                                </h2>
                                <p className="mt-3 max-w-2xl leading-7 text-slate-400">
                                    Zona de administrare rămâne separată de
                                    site-ul public, pe app.rentier.ro.
                                </p>
                            </div>
                            <a
                                href={loginUrl}
                                className="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-white px-5 text-sm font-bold text-[#07111f] transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80"
                            >
                                Autentificare
                                <ArrowRight
                                    className="size-4"
                                    aria-hidden="true"
                                />
                            </a>
                        </div>
                    </section>
                </main>

                <footer className="border-t border-white/10">
                    <div className="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-7 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-10">
                        <p>© 2026 Rentier. Administrare locativă, mai clară.</p>
                        <p>Construit pentru proprietari din România.</p>
                    </div>
                </footer>
            </div>
        </>
    );
}
