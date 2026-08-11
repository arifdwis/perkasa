// Feature flags for staged rollout.
//
// koperasiAktivasiTerbuka: controls whether the koperasi activation flow
// (langkah 2 & 3 — cari data lalu buat username/kata sandi) is offered in the
// UI. While false, pendaftaran tetap berjalan dan tersimpan; hanya tautan
// menuju halaman aktivasi yang disembunyikan.
//
// Routenya sendiri tetap terdaftar, jadi menyalakan kembali cukup dengan
// mengubah nilai ini menjadi true lalu build ulang.
export const koperasiAktivasiTerbuka = false

// tautanDaftarKoperasiTerlihat: menampilkan tautan silang menuju pendaftaran
// koperasi di kaki halaman /login dan /register. Halaman /register/koperasi
// tetap terdaftar dan bisa diakses langsung; ini hanya soal ditawarkan atau
// tidak dari halaman auth alumni.
export const tautanDaftarKoperasiTerlihat = false
