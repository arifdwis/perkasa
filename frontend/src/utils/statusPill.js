export function statusPill(status) {
  switch (status) {
    case 'active':
    case 'verified':
    case 'selesai':
      return 'bg-emerald-50 text-emerald-700 border border-emerald-200'
    case 'pending':
    case 'menunggu_konfirmasi':
      return 'bg-amber-50 text-amber-700 border border-amber-200'
    case 'rejected':
    case 'suspended':
    case 'dibatalkan':
      return 'bg-red-50 text-red-700 border border-red-200'
    case 'inactive':
    case 'out_of_stock':
      return 'bg-slate-50 text-slate-500 border border-slate-200'
    case 'diproses':
    case 'dalam_pengantaran':
      return 'bg-blue-50 text-blue-700 border border-blue-200'
    default:
      return 'bg-slate-50 text-slate-500 border border-slate-200'
  }
}
