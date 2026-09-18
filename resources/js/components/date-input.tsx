import { CalendarDays } from 'lucide-react';
import { useRef, useState } from 'react';
import type { ComponentProps } from 'react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import {
    dateInputFormatMessage,
    dateInputPlaceholder,
    formatDateForInput,
    parseDateInputToIso,
} from '@/lib/date';
import { DEFAULT_LOCALE } from '@/lib/locale';

type Props = Omit<
    ComponentProps<typeof Input>,
    'defaultValue' | 'name' | 'onChange' | 'type' | 'value'
> & {
    name: string;
    defaultValue?: string | null;
    locale?: string;
    onValueChange?: (value: string) => void;
    error?: string;
};

export default function DateInput({
    name,
    defaultValue,
    locale = DEFAULT_LOCALE,
    onValueChange,
    error,
    onBlur,
    id,
    required,
    disabled,
    'aria-describedby': ariaDescribedBy,
    ...props
}: Props) {
    const initialValue = defaultValue?.slice(0, 10) ?? '';
    const [displayValue, setDisplayValue] = useState(
        formatDateForInput(initialValue, locale),
    );
    const [isoValue, setIsoValue] = useState(initialValue);
    const [hasFormatError, setHasFormatError] = useState(false);
    const pickerRef = useRef<HTMLInputElement>(null);
    const hintId = id ? `${id}-format-hint` : undefined;
    const errorId = id ? `${id}-error` : undefined;
    const visibleServerError = hasFormatError ? undefined : error;
    const describedBy = [
        ariaDescribedBy,
        hintId,
        visibleServerError ? errorId : undefined,
    ]
        .filter(Boolean)
        .join(' ');

    const updateValue = (value: string) => {
        const parsedValue = parseDateInputToIso(value, locale);

        setDisplayValue(value);
        setHasFormatError(parsedValue === null);

        if (parsedValue !== null) {
            setIsoValue(parsedValue);
            onValueChange?.(parsedValue);
        } else {
            setIsoValue('');
            onValueChange?.('');
        }
    };

    return (
        <div className="space-y-1">
            <input
                type="hidden"
                name={name}
                value={isoValue}
                disabled={disabled}
            />
            <div className="relative">
                <Input
                    {...props}
                    id={id}
                    type="text"
                    value={displayValue}
                    placeholder={dateInputPlaceholder(locale)}
                    inputMode="numeric"
                    autoComplete="off"
                    aria-required={required || undefined}
                    disabled={disabled}
                    aria-invalid={
                        hasFormatError ||
                        Boolean(visibleServerError) ||
                        props['aria-invalid']
                    }
                    aria-describedby={describedBy || undefined}
                    className={`${props.className ?? ''} pr-10`}
                    onChange={(event) => updateValue(event.target.value)}
                    onBlur={(event) => {
                        const parsedValue = parseDateInputToIso(
                            event.target.value,
                            locale,
                        );

                        if (parsedValue) {
                            setDisplayValue(
                                formatDateForInput(parsedValue, locale),
                            );
                        }

                        onBlur?.(event);
                    }}
                />
                <button
                    type="button"
                    className="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-muted-foreground transition-colors hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                    aria-label={
                        locale === 'ro-RO'
                            ? 'Alege data din calendar'
                            : 'Choose date from calendar'
                    }
                    disabled={disabled}
                    onClick={() => pickerRef.current?.showPicker()}
                >
                    <CalendarDays className="size-4" aria-hidden="true" />
                </button>
            </div>
            <Input
                ref={pickerRef}
                type="date"
                value={isoValue}
                disabled={disabled}
                tabIndex={-1}
                aria-hidden="true"
                className="pointer-events-none absolute size-px opacity-0"
                onChange={(event) => {
                    const value = event.target.value;

                    setIsoValue(value);
                    setDisplayValue(formatDateForInput(value, locale));
                    setHasFormatError(false);
                    onValueChange?.(value);
                }}
            />
            <p
                id={hintId}
                className={
                    hasFormatError
                        ? 'text-xs text-destructive'
                        : 'text-xs text-muted-foreground'
                }
            >
                {dateInputFormatMessage(locale)}
            </p>
            <InputError id={errorId} message={visibleServerError} />
        </div>
    );
}
