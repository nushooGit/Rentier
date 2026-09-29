export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-9 items-center justify-center rounded-xl bg-emerald-300 text-sm font-black tracking-tight text-[#07111f] shadow-sm shadow-emerald-400/20">
                R
            </div>
            <div className="ml-1 grid flex-1 text-left">
                <span className="truncate text-[15px] leading-tight font-semibold tracking-tight">
                    Rentier
                </span>
                <span className="truncate text-[10px] font-medium uppercase tracking-[0.18em] text-sidebar-foreground/45">
                    Administrare chirii
                </span>
            </div>
        </>
    );
}
