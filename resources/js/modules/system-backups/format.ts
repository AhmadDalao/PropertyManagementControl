export function formatBackupBytes(value: number, locale: string): string {
    if (value <= 0) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    const unit = Math.min(
        Math.floor(Math.log(value) / Math.log(1024)),
        units.length - 1,
    );
    const amount = value / 1024 ** unit;

    return `${new Intl.NumberFormat(locale === 'ar' ? 'ar-SA' : 'en', {
        maximumFractionDigits: amount >= 10 || unit === 0 ? 0 : 1,
    }).format(amount)} ${units[unit]}`;
}
