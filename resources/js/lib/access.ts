import type { AppUser } from '@/types';

export function canCreateOperationalRecord(user: AppUser | null): boolean {
    return (
        !user?.property_scope.restricted || user.property_scope.has_assignments
    );
}
