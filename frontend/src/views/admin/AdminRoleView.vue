<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Checkbox from 'primevue/checkbox'
import Toast from 'primevue/toast'
import MultiSelect from 'primevue/multiselect'
import AdminPageHeader from '../../components/admin/AdminPageHeader.vue'
import AdminPanel from '../../components/admin/AdminPanel.vue'
import AdminState from '../../components/admin/AdminState.vue'
import AdminSlideOver from '../../components/admin/AdminSlideOver.vue'
import AdminConfirmModal from '../../components/admin/AdminConfirmModal.vue'
import AdminPaginator from '../../components/admin/AdminPaginator.vue'

const toast = useToast()

const activeTab = ref('roles')
const roles = ref([])
const permissions = ref([])
const loadingRoles = ref(true)
const users = ref([])
const totalUsers = ref(0)
const loadingUsers = ref(true)
const searchUser = ref('')
const currentUserPage = ref(1)

// Role form slide-over
const roleFormVisible = ref(false)
const isEditRole = ref(false)
const roleForm = ref({ id: '', name: '', permissions: [] })
const savingRole = ref(false)

// Assign form slide-over
const assignVisible = ref(false)
const assignForm = ref({ userId: '', userName: '', roles: [] })
const savingAssign = ref(false)

// Delete confirm
const deleteVisible = ref(false)
const deleteTarget = ref(null)
const deleteLoading = ref(false)

const fetchRolesAndPermissions = async () => {
  loadingRoles.value = true
  try {
    const [rolesRes, permRes] = await Promise.all([axios.get('/admin/roles'), axios.get('/admin/permissions')])
    roles.value = rolesRes.data
    permissions.value = permRes.data
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal memuat data.', life: 3000 })
  } finally { loadingRoles.value = false }
}

const fetchUsers = async (page = 1) => {
  loadingUsers.value = true
  currentUserPage.value = page
  try {
    const response = await axios.get('/admin/alumni', { params: { page, search: searchUser.value } })
    users.value = response.data.data.map(profile => ({
      id: profile.user?.id, name: profile.user?.name, email: profile.user?.email,
      nim: profile.nim, prodi: profile.program_studi, roles: profile.user?.roles?.map(r => r.name) || []
    })).filter(u => u.id)
    totalUsers.value = response.data.total
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal memuat pengguna.', life: 3000 })
  } finally { loadingUsers.value = false }
}

onMounted(() => { fetchRolesAndPermissions() })

const handleTabChange = (tab) => { activeTab.value = tab; if (tab === 'users') fetchUsers(1); else fetchRolesAndPermissions() }

// Role CRUD
const openNewRole = () => { isEditRole.value = false; roleForm.value = { id: '', name: '', permissions: [] }; roleFormVisible.value = true }
const editRole = (role) => { isEditRole.value = true; roleForm.value = { id: role.id, name: role.name, permissions: role.permissions.map(p => p.name) }; roleFormVisible.value = true }

const saveRole = async () => {
  if (!roleForm.value.name.trim()) return
  savingRole.value = true
  try {
    if (isEditRole.value) {
      await axios.put(`/admin/roles/${roleForm.value.id}`, { name: roleForm.value.name, permissions: roleForm.value.permissions })
      toast.add({ severity: 'success', summary: 'Sukses', detail: 'Role berhasil diperbarui.', life: 3000 })
    } else {
      await axios.post('/admin/roles', { name: roleForm.value.name, permissions: roleForm.value.permissions })
      toast.add({ severity: 'success', summary: 'Sukses', detail: 'Role baru berhasil dibuat.', life: 3000 })
    }
    roleFormVisible.value = false; fetchRolesAndPermissions()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Terjadi kesalahan.', life: 3000 })
  } finally { savingRole.value = false }
}

const openDeleteRole = (role) => {
  deleteTarget.value = role
  deleteVisible.value = true
}

const handleDeleteRole = async () => {
  deleteLoading.value = true
  try {
    await axios.delete(`/admin/roles/${deleteTarget.value.id}`)
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Role berhasil dihapus.', life: 3000 })
    deleteVisible.value = false; fetchRolesAndPermissions()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Terjadi kesalahan.', life: 3000 })
  } finally { deleteLoading.value = false }
}

// Matrix
const isPermissionAssigned = (role, permissionName) => role.permissions.some(p => p.name === permissionName)

const toggleMatrixPermission = async (role, permissionName) => {
  if (role.name === 'super_admin') { toast.add({ severity: 'warn', summary: 'Perhatian', detail: 'Role Super Admin dilindungi.', life: 3000 }); return }
  let newPermissions = role.permissions.map(p => p.name)
  if (isPermissionAssigned(role, permissionName)) newPermissions = newPermissions.filter(n => n !== permissionName)
  else newPermissions.push(permissionName)
  try { await axios.put(`/admin/roles/${role.id}`, { name: role.name, permissions: newPermissions }); toast.add({ severity: 'success', summary: 'Diperbarui', detail: `Hak akses "${permissionName}" disinkronkan.`, life: 2000 }); fetchRolesAndPermissions() }
  catch (err) { toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message, life: 3000 }) }
}

// Assign role
const openAssignRole = (user) => { assignForm.value = { userId: user.id, userName: user.name, roles: [...user.roles] }; assignVisible.value = true }
const saveAssign = async () => {
  savingAssign.value = true
  try { await axios.post(`/admin/users/${assignForm.value.userId}/assign-role`, { roles: assignForm.value.roles }); toast.add({ severity: 'success', summary: 'Sukses', detail: 'Role berhasil diperbarui.', life: 3000 }); assignVisible.value = false; fetchUsers(currentUserPage.value) }
  catch (err) { toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message, life: 3000 }) }
  finally { savingAssign.value = false }
}

const totalPages = () => Math.ceil(totalUsers.value / 15)
</script>

<template>
  <div class="space-y-6">
    <Toast />
    <AdminPageHeader icon="solar:key-bold-duotone" title="Manajemen Role & Hak Akses" subtitle="Kelola matriks otorisasi role, permission granular, dan penugasan hak akses pengguna." />

    <!-- Tabs -->
    <div class="flex gap-1 p-1 bg-white border border-slate-200 rounded-xl w-fit">
      <button class="px-4 py-2 text-xs font-bold rounded-lg transition-all"
              :class="activeTab === 'roles' ? 'bg-primary text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100'"
              @click="handleTabChange('roles')">
        <i class="pi pi-shield mr-1"></i> Daftar Role
      </button>
      <button class="px-4 py-2 text-xs font-bold rounded-lg transition-all"
              :class="activeTab === 'matrix' ? 'bg-primary text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100'"
              @click="handleTabChange('matrix')">
        <i class="pi pi-table mr-1"></i> Matriks Permission
      </button>
      <button class="px-4 py-2 text-xs font-bold rounded-lg transition-all"
              :class="activeTab === 'users' ? 'bg-primary text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100'"
              @click="handleTabChange('users')">
        <i class="pi pi-users mr-1"></i> Penugasan Role
      </button>
    </div>

    <!-- Tab: Roles -->
    <div v-if="activeTab === 'roles'" class="space-y-4">
      <div class="flex justify-between items-center">
        <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Daftar Hak Akses Sistem</h3>
        <Button label="Tambah Role" icon="pi pi-plus" size="small" @click="openNewRole" />
      </div>
      <div class="space-y-2.5">
        <AdminState v-if="loadingRoles" mode="loading" />
        <template v-else>
          <div v-for="role in roles" :key="role.id"
               class="bg-white border border-slate-200 rounded-xl px-4 py-3
                      flex items-center gap-4 hover:border-primary/40 hover:shadow-sm transition-all">
            <div class="w-11 h-11 rounded-xl bg-primary-soft text-primary flex items-center justify-center shrink-0">
              <i class="pi pi-shield text-lg"></i>
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-bold text-slate-800 capitalize">{{ role.name.replace('_', ' ') }}</p>
              <p class="text-xs text-slate-400 font-mono">{{ role.name }}</p>
            </div>
            <div class="hidden md:flex flex-wrap gap-1 max-w-xs">
              <span v-for="perm in role.permissions.slice(0, 3)" :key="perm.id"
                    class="px-2 py-0.5 bg-slate-100 text-slate-600 text-[10px] rounded-full font-mono font-semibold">{{ perm.name }}</span>
              <span v-if="role.permissions.length > 3" class="px-2 py-0.5 bg-slate-100 text-slate-500 text-[10px] rounded-full font-semibold">+{{ role.permissions.length - 3 }}</span>
              <span v-if="role.name === 'super_admin'" class="px-2 py-0.5 bg-primary-soft text-primary text-[10px] rounded-full font-semibold">* All</span>
            </div>
            <div class="flex gap-1.5" v-if="role.name !== 'super_admin'">
              <Button icon="pi pi-pencil" size="small" severity="secondary" outlined @click="editRole(role)" />
              <Button icon="pi pi-trash" size="small" severity="danger" outlined @click="openDeleteRole(role)" />
            </div>
            <span v-else class="text-[10px] font-semibold text-primary bg-primary-soft px-2.5 py-1 rounded-full">Protected</span>
          </div>
          <AdminState v-if="!roles.length && !loadingRoles" mode="empty" text="Belum ada role sistem." />
        </template>
      </div>
    </div>

    <!-- Tab: Matrix -->
    <div v-if="activeTab === 'matrix'" class="space-y-4">
      <div>
        <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Matriks Role vs Hak Akses</h3>
        <p class="text-xs text-slate-400">Centang kotak untuk menyinkronkan izin akses secara instan.</p>
      </div>
      <AdminPanel noPad>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr>
                <th class="p-4 min-w-[240px] text-[10px] font-bold text-slate-500 uppercase tracking-wider bg-slate-50 border-b border-slate-200">Hak Akses (Permission)</th>
                <th v-for="role in roles" :key="role.id" class="p-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider bg-slate-50 border-b border-slate-200">
                  <span class="capitalize">{{ role.name.replace('_', ' ') }}</span>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="perm in permissions" :key="perm.id" class="border-b border-slate-100 hover:bg-slate-50/60 transition-colors">
                <td class="p-4">
                  <div class="font-mono text-slate-700 text-sm font-semibold">{{ perm.name }}</div>
                  <div class="text-[10px] text-slate-400 uppercase">Guard: {{ perm.guard_name }}</div>
                </td>
                <td v-for="role in roles" :key="role.id" class="p-4 text-center">
                  <Checkbox :binary="true" :modelValue="isPermissionAssigned(role, perm.name)" :disabled="role.name === 'super_admin'" class="scale-90" @update:modelValue="toggleMatrixPermission(role, perm.name)" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </AdminPanel>
    </div>

    <!-- Tab: Users -->
    <div v-if="activeTab === 'users'" class="space-y-4">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Penugasan Role Pengguna</h3>
        <div class="w-full sm:w-80">
          <div class="relative flex items-center w-full">
            <i class="pi pi-search absolute left-3.5 text-slate-400" />
            <InputText v-model="searchUser" placeholder="Cari user..." class="w-full !pl-10" @input="fetchUsers(1)" />
          </div>
        </div>
      </div>
      <div class="space-y-2.5">
        <AdminState v-if="loadingUsers" mode="loading" />
        <template v-else>
          <div v-for="user in users" :key="user.id"
               class="bg-white border border-slate-200 rounded-xl px-4 py-3
                      flex items-center gap-4 hover:border-primary/40 hover:shadow-sm transition-all">
            <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0 font-black text-xs">
              {{ user.name?.substring(0, 2).toUpperCase() }}
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-bold text-slate-800 truncate">{{ user.name }}</p>
              <p class="text-xs text-slate-400 truncate">{{ user.email }}</p>
            </div>
            <div class="hidden md:block text-xs text-slate-500 w-32">
              <span class="font-semibold">{{ user.nim }}</span>
              <span class="block text-slate-400">{{ user.prodi }}</span>
            </div>
            <div class="hidden md:flex flex-wrap gap-1 max-w-[200px]">
              <span v-for="r in user.roles" :key="r"
                    class="px-2 py-0.5 text-[10px] font-semibold rounded-full"
                    :class="r === 'super_admin' ? 'bg-red-50 text-red-600 border border-red-200' : 'bg-slate-100 text-slate-600 border border-slate-200'">
                {{ r.replace('_', ' ') }}
              </span>
              <span v-if="user.roles.length === 0" class="text-[10px] text-slate-400 italic">Belum ada role</span>
            </div>
            <Button label="Ubah Role" icon="pi pi-user-edit" size="small" outlined class="text-xs" @click="openAssignRole(user)" />
          </div>
          <AdminState v-if="!users.length && !loadingUsers" mode="empty" text="Pengguna tidak ditemukan." />
        </template>
      </div>
      <!-- User pagination -->
      <AdminPaginator :total="totalUsers" :rows="15" :first="(currentUserPage-1)*15" @page="e => fetchUsers(e.page)" />
    </div>

    <!-- Role Form Slide-Over -->
    <AdminSlideOver :visible="roleFormVisible" @update:visible="roleFormVisible = $event"
                    :icon="isEditRole ? 'solar:pen-bold' : 'solar:add-circle-bold'"
                    :title="isEditRole ? 'Edit Role' : 'Tambah Role Baru'"
                    subtitle="Konfigurasi nama role dan hak akses"
                    width="460px">
      <div class="space-y-5">
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Nama Role</label>
          <InputText v-model="roleForm.name" placeholder="Contoh: manager_toko" :disabled="isEditRole" class="w-full text-sm" />
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pilih Permissions</label>
          <MultiSelect v-model="roleForm.permissions" :options="permissions" optionLabel="name" optionValue="name" placeholder="Pilih Hak Akses" display="chip" class="w-full text-sm" />
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button label="Batal" severity="secondary" size="small" outlined @click="roleFormVisible = false" />
          <Button label="Simpan" :loading="savingRole" size="small" @click="saveRole" />
        </div>
      </template>
    </AdminSlideOver>

    <!-- Assign Role Slide-Over -->
    <AdminSlideOver :visible="assignVisible" @update:visible="assignVisible = $event"
                    icon="solar:user-circle-bold" title="Ubah Role Pengguna" subtitle="Tetapkan role untuk pengguna"
                    width="420px">
      <div class="space-y-5">
        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
          <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pengguna</span>
          <span class="font-bold text-slate-700 text-sm">{{ assignForm.userName }}</span>
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pilih Roles</label>
          <MultiSelect v-model="assignForm.roles" :options="roles" optionLabel="name" optionValue="name" placeholder="Pilih Role" display="chip" class="w-full text-sm">
            <template #option="slotProps"><span class="capitalize">{{ slotProps.option.name.replace('_', ' ') }}</span></template>
          </MultiSelect>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button label="Batal" severity="secondary" size="small" outlined @click="assignVisible = false" />
          <Button label="Simpan" :loading="savingAssign" size="small" @click="saveAssign" />
        </div>
      </template>
    </AdminSlideOver>

    <!-- Delete Confirm Modal -->
    <AdminConfirmModal :visible="deleteVisible" @update:visible="deleteVisible = $event"
      title="Hapus Role"
      :message="`Apakah Anda yakin ingin menghapus role &quot;${deleteTarget?.name}&quot;?`"
      icon="solar:trash-bin-minimalistic-bold"
      tone="danger"
      confirmLabel="Hapus"
      :loading="deleteLoading"
      @confirm="handleDeleteRole" />
  </div>
</template>
