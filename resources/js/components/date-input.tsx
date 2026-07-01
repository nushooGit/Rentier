import { useState } from 'react';
import type { ComponentProps } from 'react';
import { Input } from '@/components/ui/input';
import { formatDateShort } from '@/lib/date';
import { DEFAULT_LOCALE } from '@/lib/locale';

type Props = Omit<
    ComponentProps<typeof Input>,
    'defaultValue' | 'name' | 'type'
> & {
    name: string;
    defaultValue?: string | null;
    locale?: string;
};

export default function DateInput({
    name,
    defaultValue,
    locale = DEFAULT_LOCALE,
    onChange,
    onBlur,
    ...props
}: Props) {
    const [selectedValue, setSelectedValue] = useState(defaultValue ?? '');

    return (
        <>
            <Input
                {...props}
                type="date"
                name={name}
                defaultValue={defaultValue ?? ''}
                onChange={(event) => {
                    setSelectedValue(event.target.value);
                    onChange?.(event);
                }}
                onBlur={(event) => {
                    onBlur?.(event);
                }}
            />
            {selectedValue ? (
                <p className="text-xs text-muted-foreground">
                    {locale === 'ro-RO' ? 'Data selectată' : 'Selected date'}:{' '}
                    {formatDateShort(selectedValue, locale)}
                </p>
            ) : null}
        </>
    );
}
