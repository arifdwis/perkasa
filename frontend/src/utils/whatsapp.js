// Normalise an Indonesian phone number into the digits-only international
// form wa.me expects: 08123456789 / +628123456789 / 628123456789 -> 628123456789
export const normalizeWhatsapp = (raw) => {
  if (!raw) return ''
  let n = String(raw).replace(/[^\d+]/g, '')
  if (n.startsWith('+')) n = n.slice(1)
  if (n.startsWith('0')) n = '62' + n.slice(1)
  else if (!n.startsWith('62')) n = '62' + n
  return n
}

// wa.me opens WhatsApp Web (or the desktop/phone app) with the message
// prefilled, so the admin only has to press send from their own account.
export const waLink = (phone, message) =>
  `https://wa.me/${normalizeWhatsapp(phone)}?text=${encodeURIComponent(message)}`

export const pesanAktivasi = ({ nama, tautan, kedaluwarsa }) => {
  const tanggal = kedaluwarsa
    ? new Date(kedaluwarsa).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
    : null

  return [
    `Halo ${nama},`,
    '',
    'Pendaftaran Anda sebagai anggota Koperasi Alumni FEB Universitas Mulawarman sudah kami terima.',
    '',
    'Silakan aktifkan akun Anda melalui tautan berikut untuk membuat username dan kata sandi:',
    tautan,
    '',
    tanggal
      ? `Tautan berlaku sampai ${tanggal} dan hanya dapat digunakan satu kali.`
      : 'Tautan ini hanya dapat digunakan satu kali.',
    '',
    'Terima kasih.',
    'Admin Koperasi Alumni FEB Unmul',
  ].join('\n')
}
