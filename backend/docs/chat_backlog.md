# Rancangan Backlog Chat Internal (Fase Lanjutan)

Dokumen ini berisi rancangan teknis dan arsitektur backlog untuk fitur chat internal real-time antara Pembeli dan Toko (Penjual) di platform Marketplace Alumni FEB Universitas Mulawarman.

---

## 1. Arsitektur Komunikasi Real-Time
Untuk mendukung komunikasi dua arah secara instan, platform akan diintegrasikan dengan teknologi WebSocket:
* **Backend**: Laravel Reverb (WebSocket server bawaan Laravel 11+) atau Pusher / Socket.io.
* **Frontend**: Laravel Echo + Pusher JS SDK.

---

## 2. Rancangan Skema Database (UUID v7)

### Tabel `chat_rooms`
Menampung data sesi chat/percakapan antara pembeli dan toko.
```sql
CREATE TABLE chat_rooms (
    id UUID PRIMARY KEY, -- UUID v7
    store_id UUID NOT NULL, -- Relasi ke toko (penjual)
    user_id UUID NOT NULL, -- Relasi ke user (pembeli)
    last_message_at TIMESTAMP NULL, -- Indikator sorting chat terbaru
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(store_id, user_id) -- Batasi satu percakapan per pasangan pembeli-toko
);
```

### Tabel `chat_messages`
Menyimpan riwayat pesan di setiap room.
```sql
CREATE TABLE chat_messages (
    id UUID PRIMARY KEY, -- UUID v7
    chat_room_id UUID NOT NULL,
    sender_id UUID NOT NULL, -- User ID pengirim pesan
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE, -- Indikator unread chat
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (chat_room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 3. Spesifikasi Endpoint API (RESTful)

| Method | Endpoint | Deskripsi | Parameter Request | Response |
| :--- | :--- | :--- | :--- | :--- |
| `POST` | `/api/chats/rooms` | Membuat atau mendapatkan chat room | `{ "store_id": "uuid" }` | Sesi room detail |
| `GET` | `/api/chats/rooms` | Daftar room chat user aktif | - | Paginated list rooms |
| `GET` | `/api/chats/rooms/{id}/messages` | Riwayat pesan per room | `?page=1` | Paginated messages |
| `POST` | `/api/chats/rooms/{id}/messages` | Mengirim pesan baru | `{ "message": "text" }` | Pesan yang terkirim |
| `POST` | `/api/chats/rooms/{id}/read` | Mark read semua pesan di room | - | `{ "success": true }` |

---

## 4. Alur Integrasi Real-Time (Event Broadcaster)

1. **Broadcasting Event**: Saat backend menyimpan pesan baru via `POST /api/chats/rooms/{id}/messages`, Laravel memicu event `MessageSent` yang mengimplementasikan `ShouldBroadcast`.
2. **Channel Authorization**: Room chat diproteksi menggunakan Private Channel:
   ```php
   Broadcast::channel('chat.room.{roomId}', function ($user, $roomId) {
       $room = ChatRoom::find($roomId);
       return $room && ($room->user_id === $user->id || $room->store->alumniProfile->user_id === $user->id);
   });
   ```
3. **Echo Listener (Frontend Vue 3)**:
   ```javascript
   import Echo from 'laravel-echo'
   
   window.Echo.private(`chat.room.${roomId}`)
     .listen('MessageSent', (e) => {
       messages.value.push(e.message)
       // Auto scroll to bottom
     })
   ```

---

## 5. Fitur Pendukung & Optimasi UX

* **Indikator Sedang Mengetik (Typing Indicator)**:
  Menggunakan `Echo.private().whisper('typing', { name: currentUser.name })` untuk memberikan feedback visual saat lawan bicara sedang mengetik.
* **Badge Jumlah Pesan Belum Dibaca (Unread Count)**:
  Setiap client berlangganan ke global user notification channel untuk mendapatkan pembaruan real-time unread messages count di navbar utama.
* **Integrasi Konteks Produk (Contextual Message)**:
  Saat pembeli menekan tombol chat dari halaman produk, detail produk (nama, harga, gambar) akan disematkan di bagian atas pesan pertama sebagai lampiran konteks.
