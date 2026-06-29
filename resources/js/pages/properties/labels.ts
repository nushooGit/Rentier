import type { PropertyStatus, PropertyType } from '@/types';

export const propertyTypeLabels: Record<PropertyType, string> = {
    studio: 'Garsonieră',
    apartment: 'Apartament',
    house: 'Casă',
    commercial_space: 'Spațiu comercial',
    office: 'Birou',
    other: 'Altul',
};

export const propertyStatusLabels: Record<PropertyStatus, string> = {
    available: 'Liberă',
    occupied: 'Ocupată',
    renovation: 'În renovare',
    inactive: 'Inactivă',
};

export function propertyTypeLabel(value: PropertyType) {
    return propertyTypeLabels[value] ?? value;
}

export function propertyStatusLabel(value: PropertyStatus) {
    return propertyStatusLabels[value] ?? value;
}
