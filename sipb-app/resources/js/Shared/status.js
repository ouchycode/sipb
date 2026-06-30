export function statusLabel(status) {
    return {
        tersedia: 'Tersedia',
        sudah_diambil: 'Sudah diambil',
        kadaluarsa: 'Kadaluarsa',
    }[status] ?? status;
}

export function statusClass(status) {
    return {
        tersedia: 'border-[#00bf8e]/20 bg-[#00bf8e]/10 text-[#00bf8e]',
        sudah_diambil: 'border-[#dc2626]/20 bg-[#dc2626]/10 text-[#dc2626]',
        kadaluarsa: 'border-[#7957d5]/20 bg-[#7957d5]/10 text-[#7957d5]',
    }[status] ?? 'border-[#747a8b]/20 bg-[#f6f7fa] text-[#747a8b]';
}

export function maskNim(nim) {
    if (!nim) return '-';
    const str = String(nim);
    if (str.length <= 4) return str;
    return '*'.repeat(str.length - 4) + str.slice(-4);
}

export function formatDate(value, options = {}) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: options.dateStyle ?? 'medium',
        timeStyle: options.timeStyle ?? 'short',
    }).format(new Date(value));
}
