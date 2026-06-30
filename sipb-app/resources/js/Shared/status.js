export function statusLabel(status) {
    return {
        perlu_revisi: 'Tidak aktif',
        tersedia: 'Tersedia',
        dalam_proses_klaim: 'Tersedia',
        sudah_diambil: 'Sudah diambil',
        ditolak: 'Tidak aktif',
        kadaluarsa: 'Kadaluarsa',
    }[status] ?? status;
}

export function statusClass(status) {
    return {
        perlu_revisi: 'border-[#747a8b]/20 bg-[#f6f7fa] text-[#747a8b]',
        tersedia: 'border-[#00bf8e]/20 bg-[#00bf8e]/10 text-[#00bf8e]',
        dalam_proses_klaim: 'border-[#00bf8e]/20 bg-[#00bf8e]/10 text-[#00bf8e]',
        sudah_diambil: 'border-[#dc2626]/20 bg-[#dc2626]/10 text-[#dc2626]',
        ditolak: 'border-[#747a8b]/20 bg-[#f6f7fa] text-[#747a8b]',
        kadaluarsa: 'border-[#7957d5]/20 bg-[#7957d5]/10 text-[#7957d5]',
    }[status] ?? 'border-[#747a8b]/20 bg-[#f6f7fa] text-[#747a8b]';
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
