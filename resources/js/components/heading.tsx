import { cn } from '@/lib/utils';

export default function Heading({
    title,
    description,
    variant = 'default',
    eyebrow,
    className,
}: {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
    eyebrow?: string;
    className?: string;
}) {
    return (
        <header
            className={cn(
                variant === 'small' ? 'space-y-1' : 'mb-8 space-y-1.5',
                className,
            )}
        >
            {eyebrow ? (
                <p className="text-[10px] font-semibold uppercase tracking-[0.18em] text-primary">
                    {eyebrow}
                </p>
            ) : null}
            <h2
                className={
                    variant === 'small'
                        ? 'text-xl font-semibold tracking-[-0.025em] sm:text-2xl'
                        : 'text-2xl font-semibold tracking-[-0.03em] sm:text-3xl'
                }
            >
                {title}
            </h2>
            {description ? (
                <p className="max-w-2xl text-sm leading-6 text-muted-foreground">{description}</p>
            ) : null}
        </header>
    );
}
