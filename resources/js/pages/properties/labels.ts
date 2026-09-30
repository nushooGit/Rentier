import { currentAppLocale } from '@/lib/locale';
import type { PropertyStatus, PropertyType } from '@/types';

export const propertyTypeLabels: Record<PropertyType, string> = {
    studio: 'Garsonieră',
    apartment: 'Apartament',
    house: 'Casă',
    commercial_space: 'Spațiu comercial',
    office: 'Birou',
    other: 'Altul',
};

const propertyTypeLabelsEn: Record<PropertyType, string> = {
    studio: 'Studio',
    apartment: 'Apartment',
    house: 'House',
    commercial_space: 'Commercial space',
    office: 'Office',
    other: 'Other',
};

export const propertyStatusLabels: Record<PropertyStatus, string> = {
    available: 'Liberă',
    occupied: 'Ocupată',
    renovation: 'În renovare',
    inactive: 'Inactivă',
};

const propertyStatusLabelsEn: Record<PropertyStatus, string> = {
    available: 'Available',
    occupied: 'Occupied',
    renovation: 'Under renovation',
    inactive: 'Inactive',
};

export function propertyTypeLabel(value: PropertyType) {
    const labels = currentAppLocale() === 'en' ? propertyTypeLabelsEn : propertyTypeLabels;
    return labels[value] ?? value;
}

export function propertyStatusLabel(value: PropertyStatus) {
    const labels = currentAppLocale() === 'en' ? propertyStatusLabelsEn : propertyStatusLabels;
    return labels[value] ?? value;
}
