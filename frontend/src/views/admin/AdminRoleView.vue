<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Checkbox from 'primevue/checkbox'
import Dialog from 'primevue/dialog'
import Toast from 'primevue/toast'
import MultiSelect from 'primevue/multiselect'

const router = useRouter()
const toast = useToast()

const activeTab = ref('roles') // 'roles' | 'matrix' | 'users'

// Roles & Permissions state
const roles = ref([])
const permissions = ref([])
const loadingRoles = ref(true)

// User assignment state
const users = ref([])
const totalUsers = ref(0)
const loadingUsers = ref(true)
const searchUser = ref('')

// Dialog state
const roleDialog = ref(false)
const isEditRole = ref(false)
const roleForm = ref({
  id: '',
  name: '',
  permissions: []
})
const savingRole = ref(false)

const assignDialog = ref(false)
const assignForm = ref({
  userId: '',
  userName: '',
  roles: []
})
const savingAssign = ref(false)

// Fetch all roles and permissions
const fetchRolesAndPermissions = async () => {
  loadingRoles.value = true
  try {
    const [rolesRes, permRes] = await Promise.all([
      axios.get('/admin/roles'),
      axios.get('/admin/permissions')
    ])
    roles.value = rolesRes.data
    permissions.value = permRes.data
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Gagal Memuat Data',
      detail: err.response?.data?.message || 'Gagal memuat role dan permission.',
      life: 3000
    })
  } finally {
    loadingRoles.value = false
  }
}

// Fetch registered users (via alumni list but mapped to users)
const fetchUsers = async (page = 1) => {
  loadingUsers.value = true
  try {
    const params = {
      page,
      search: searchUser.value
    }
    const response = await axios.get('/admin/alumni', { params })
    // Extract user objects from profiles
    users.value = response.data.data.map(profile => ({
      id: profile.user?.id,
      name: profile.user?.name,
      email: profile.user?.email,
      nim: profile.nim,
      prodi: profile.program_studi,
      roles: profile.user?.roles?.map(r => r.name) || []
    })).filter(u => u.id)
    totalUsers.value = response.data.total
  } catch (err) {
    console.error(err)
    toast.add({
      severity: 'error',
      summary: 'Gagal Memuat Pengguna',
      detail: 'Gagal memuat daftar pengguna.',
      life: 3000
    })
  } finally {
    loadingUsers.value = false
  }
}

onMounted(() => {
  fetchRolesAndPermissions()
})

const handleTabChange = (tab) => {
  activeTab.value = tab
  if (tab === 'users') {
    fetchUsers(1)
  } else {
    fetchRolesAndPermissions()
  }
}

// Role CRUD Actions
const openNewRole = () => {
  isEditRole.value = false
  roleForm.value = {
    id: '',
    name: '',
    permissions: []
  }
  roleDialog.value = true
}

const editRole = (role) => {
  isEditRole.value = true
  roleForm.value = {
    id: role.id,
    name: role.name,
    permissions: role.permissions.map(p => p.name)
  }
  roleDialog.value = true
}

const saveRole = async () => {
  if (!roleForm.value.name.trim()) return

  savingRole.value = true
  try {
    if (isEditRole.value) {
      await axios.put(`/admin/roles/${roleForm.value.id}`, {
        name: roleForm.value.name,
        permissions: roleForm.value.permissions
      })
      toast.add({ severity: 'success', summary: 'Sukses', detail: 'Role berhasil diperbarui.', life: 3000 })
    } else {
      await axios.post('/admin/roles', {
        name: roleForm.value.name,
        permissions: roleForm.value.permissions
      })
      toast.add({ severity: 'success', summary: 'Sukses', detail: 'Role baru berhasil dibuat.', life: 3000 })
    }
    roleDialog.value = false
    fetchRolesAndPermissions()
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Gagal Menyimpan',
      detail: err.response?.data?.message || 'Terjadi kesalahan.',
      life: 3000
    })
  } finally {
    savingRole.value = false
  }
}

const deleteRole = async (role) => {
  if (role.name === 'super_admin') {
    toast.add({ severity: 'error', summary: 'Ditolak', detail: 'Role Super Admin tidak dapat dihapus.', life: 3000 })
    return
  }

  if (!confirm(`Apakah Anda yakin ingin menghapus role "${role.name}"?`)) return

  try {
    await axios.delete(`/admin/roles/${role.id}`)
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Role berhasil dihapus.', life: 3000 })
    fetchRolesAndPermissions()
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Gagal Menghapus',
      detail: err.response?.data?.message || 'Terjadi kesalahan.',
      life: 3000
    })
  }
}

// Matrix Toggling
const isPermissionAssigned = (role, permissionName) => {
  return role.permissions.some(p => p.name === permissionName)
}

const toggleMatrixPermission = async (role, permissionName) => {
  if (role.name === 'super_admin') {
    toast.add({ severity: 'warn', summary: 'Perhatian', detail: 'Role Super Admin dilindungi dan memiliki semua hak akses.', life: 3000 })
    return
  }

  const currentlyHas = isPermissionAssigned(role, permissionName)
  let newPermissions = role.permissions.map(p => p.name)

  if (currentlyHas) {
    newPermissions = newPermissions.filter(name => name !== permissionName)
  } else {
    newPermissions.push(permissionName)
  }

  try {
    await axios.put(`/admin/roles/${role.id}`, {
      name: role.name,
      permissions: newPermissions
    })
    toast.add({ severity: 'success', summary: 'Matriks Diperbarui', detail: `Hak akses "${permissionName}" disinkronkan untuk role ${role.name}.`, life: 2000 })
    fetchRolesAndPermissions()
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Gagal Menyinkronkan',
      detail: err.response?.data?.message || 'Gagal mengubah permission role.',
      life: 3000
    })
  }
}

// User Assignment Actions
const openAssignRole = (user) => {
  assignForm.value = {
    userId: user.id,
    userName: user.name,
    roles: [...user.roles]
  }
  assignDialog.value = true
}

const saveAssign = async () => {
  savingAssign.value = true
  try {
    await axios.post(`/admin/users/${assignForm.value.userId}/assign-role`, {
      roles: assignForm.value.roles
    })
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Role pengguna berhasil diperbarui.', life: 3000 })
    assignDialog.value = false
    fetchUsers(1)
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Gagal Memperbarui',
      detail: err.response?.data?.message || 'Terjadi kesalahan.',
      life: 3000
    })
  } finally {
    savingAssign.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 p-4 sm:p-8">
    <Toast />
    <div class="max-w-7xl mx-auto space-y-6">
      
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 class="text-2xl font-black text-slate-800 flex items-center gap-2">
            <i class="pi pi-key text-primary text-2xl"></i> Manajemen Role & Hak Akses
          </h2>
          <p class="text-xs text-slate-500 font-medium">
            Kelola matriks otorisasi role, permission granular, dan penugasan hak akses pengguna.
          </p>
        </div>
        <Button label="Kembali ke Dashboard" icon="pi pi-home" severity="secondary" size="small" @click="router.push({ name: 'Home' })" />
      </div>

      <!-- Navigation Tabs -->
      <div class="flex gap-2 border-b border-slate-200 pb-2">
        <button 
          class="px-4 py-2 text-sm font-bold rounded-lg transition-all duration-200"
          :class="activeTab === 'roles' ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
          @click="handleTabChange('roles')"
        >
          <i class="pi pi-shield mr-1"></i> Daftar Role
        </button>
        <button 
          class="px-4 py-2 text-sm font-bold rounded-lg transition-all duration-200"
          :class="activeTab === 'matrix' ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
          @click="handleTabChange('matrix')"
        >
          <i class="pi pi-table mr-1"></i> Matriks Permission
        </button>
        <button 
          class="px-4 py-2 text-sm font-bold rounded-lg transition-all duration-200"
          :class="activeTab === 'users' ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
          @click="handleTabChange('users')"
        >
          <i class="pi pi-users mr-1"></i> Penugasan Role User
        </button>
      </div>

      <!-- Tab: Roles List -->
      <div v-if="activeTab === 'roles'" class="space-y-4">
        <div class="flex justify-between items-center">
          <h3 class="text-lg font-bold text-slate-800">Daftar Hak Akses Sistem</h3>
          <Button label="Tambah Role Baru" icon="pi pi-plus" size="small" @click="openNewRole" />
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
          <DataTable :value="roles" :loading="loadingRoles" class="p-datatable-sm">
            <template #empty>
              <div class="text-center py-8 text-slate-500 font-medium">Belum ada role sistem.</div>
            </template>
            <Column field="name" header="Nama Role" class="font-bold text-slate-800">
              <template #body="slotProps">
                <span class="capitalize">{{ slotProps.data.name.replace('_', ' ') }}</span>
                <span class="text-xs text-slate-400 font-mono ml-2">({{ slotProps.data.name }})</span>
              </template>
            </Column>
            <Column header="Permissions" class="text-slate-600">
              <template #body="slotProps">
                <div class="flex flex-wrap gap-1">
                  <span 
                    v-for="perm in slotProps.data.permissions" 
                    :key="perm.id" 
                    class="px-2 py-0.5 bg-slate-100 text-slate-700 text-xs rounded-full font-mono font-semibold"
                  >
                    {{ perm.name }}
                  </span>
                  <span v-if="slotProps.data.name === 'super_admin'" class="px-2 py-0.5 bg-primary-soft text-primary text-xs rounded-full font-semibold">
                    * Bypasses All Permissions
                  </span>
                  <span v-else-if="slotProps.data.permissions.length === 0" class="text-xs text-slate-400 italic">
                    Tidak ada permission
                  </span>
                </div>
              </template>
            </Column>
            <Column header="Aksi" class="text-center w-48">
              <template #body="slotProps">
                <div class="flex justify-center gap-1.5" v-if="slotProps.data.name !== 'super_admin'">
                  <Button icon="pi pi-pencil" size="small" severity="secondary" outlined @click="editRole(slotProps.data)" />
                  <Button icon="pi pi-trash" size="small" severity="danger" outlined @click="deleteRole(slotProps.data)" />
                </div>
                <span v-else class="text-xs font-semibold text-primary bg-primary-soft px-2.5 py-1 rounded-full">System Owner (Dilindungi)</span>
              </template>
            </Column>
          </DataTable>
        </div>
      </div>

      <!-- Tab: Matrix Grid -->
      <div v-if="activeTab === 'matrix'" class="space-y-4">
        <div>
          <h3 class="text-lg font-bold text-slate-800">Matriks Role vs Hak Akses</h3>
          <p class="text-xs text-slate-500">Centang kotak untuk menyinkronkan izin akses secara instan ke role terkait.</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-100 text-slate-600 text-xs uppercase tracking-wider font-bold">
                <th class="p-4 min-w-[240px]">Hak Akses (Permission)</th>
                <th v-for="role in roles" :key="role.id" class="p-4 text-center">
                  <span class="capitalize">{{ role.name.replace('_', ' ') }}</span>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="perm in permissions" 
                :key="perm.id" 
                class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors"
              >
                <td class="p-4">
                  <div class="font-semibold text-slate-800 text-sm font-mono">{{ perm.name }}</div>
                  <div class="text-[10px] text-slate-400 uppercase font-semibold">Guard: {{ perm.guard_name }}</div>
                </td>
                <td v-for="role in roles" :key="role.id" class="p-4 text-center">
                  <Checkbox 
                    :binary="true" 
                    :modelValue="isPermissionAssigned(role, perm.name)"
                    :disabled="role.name === 'super_admin'"
                    class="scale-90"
                    @update:modelValue="toggleMatrixPermission(role, perm.name)"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab: Assign Role User -->
      <div v-if="activeTab === 'users'" class="space-y-4">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
          <h3 class="text-lg font-bold text-slate-800">Penugasan Role Pengguna</h3>
          <div class="w-full sm:w-80">
            <span class="p-input-icon-left w-full">
              <i class="pi pi-search text-slate-400" />
              <InputText v-model="searchUser" placeholder="Cari user berdasarkan nama/NIM..." class="w-full text-sm" @input="fetchUsers(1)" />
            </span>
          </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
          <DataTable 
            :value="users" 
            lazy 
            :totalRecords="totalUsers" 
            :loading="loadingUsers" 
            paginator 
            :rows="15"
            responsiveLayout="stack" 
            breakpoint="960px"
            class="p-datatable-sm"
            @page="(event) => fetchUsers(event.page + 1)"
          >
            <template #empty>
              <div class="text-center py-8 text-slate-500 font-medium">Pengguna tidak ditemukan.</div>
            </template>
            <Column field="name" header="Nama Pengguna" class="font-bold text-slate-800">
              <template #body="slotProps">
                <div>
                  <span class="block">{{ slotProps.data.name }}</span>
                  <span class="text-xs text-slate-400 font-normal">{{ slotProps.data.email }}</span>
                </div>
              </template>
            </Column>
            <Column field="nim" header="NIM/Prodi" class="text-slate-600">
              <template #body="slotProps">
                <span class="block text-xs font-semibold">{{ slotProps.data.nim }}</span>
                <span class="text-[10px] text-slate-400">{{ slotProps.data.prodi }}</span>
              </template>
            </Column>
            <Column header="Role Saat Ini">
              <template #body="slotProps">
                <div class="flex flex-wrap gap-1">
                  <span 
                    v-for="r in slotProps.data.roles" 
                    :key="r" 
                    class="px-2 py-0.5 text-xs font-semibold rounded-full"
                    :class="r === 'super_admin' ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-700'"
                  >
                    {{ r.replace('_', ' ') }}
                  </span>
                  <span v-if="slotProps.data.roles.length === 0" class="text-xs text-slate-400 italic">
                    Belum punya role
                  </span>
                </div>
              </template>
            </Column>
            <Column header="Aksi" class="text-center">
              <template #body="slotProps">
                <Button 
                  label="Ubah Role" 
                  icon="pi pi-user-edit" 
                  size="small" 
                  outlined 
                  class="hover:bg-slate-50"
                  @click="openAssignRole(slotProps.data)" 
                />
              </template>
            </Column>
          </DataTable>
        </div>
      </div>

    </div>

    <!-- Create/Edit Role Dialog -->
    <Dialog 
      v-model:visible="roleDialog" 
      :header="isEditRole ? 'Edit Role' : 'Tambah Role Baru'" 
      :modal="true" 
      :style="{ width: '450px' }"
    >
      <div class="flex flex-col gap-4 pt-2">
        <div class="flex flex-col gap-1.5">
          <label for="roleName" class="text-xs font-bold text-slate-600 uppercase tracking-wider">Nama Role (Unique Key)</label>
          <InputText 
            id="roleName" 
            v-model="roleForm.name" 
            placeholder="Contoh: manager_toko" 
            :disabled="isEditRole"
            class="w-full text-sm"
          />
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Pilih Permissions</label>
          <MultiSelect 
            v-model="roleForm.permissions" 
            :options="permissions" 
            optionLabel="name" 
            optionValue="name" 
            placeholder="Pilih Hak Akses" 
            display="chip" 
            class="w-full text-sm"
          />
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-2 pt-4">
          <Button label="Batal" severity="secondary" size="small" outlined @click="roleDialog = false" />
          <Button label="Simpan" :loading="savingRole" size="small" @click="saveRole" />
        </div>
      </template>
    </Dialog>

    <!-- Assign Role User Dialog -->
    <Dialog 
      v-model:visible="assignDialog" 
      header="Ubah Role Pengguna" 
      :modal="true" 
      :style="{ width: '450px' }"
    >
      <div class="flex flex-col gap-4 pt-2">
        <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
          <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Pengguna</span>
          <span class="font-bold text-slate-700 text-sm">{{ assignForm.userName }}</span>
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Pilih Roles</label>
          <MultiSelect 
            v-model="assignForm.roles" 
            :options="roles" 
            optionLabel="name" 
            optionValue="name" 
            placeholder="Pilih Role Pengguna" 
            display="chip" 
            class="w-full text-sm"
          >
            <template #option="slotProps">
              <span class="capitalize">{{ slotProps.option.name.replace('_', ' ') }}</span>
            </template>
          </MultiSelect>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-2 pt-4">
          <Button label="Batal" severity="secondary" size="small" outlined @click="assignDialog = false" />
          <Button label="Simpan" :loading="savingAssign" size="small" @click="saveAssign" />
        </div>
      </template>
    </Dialog>
  </div>
</template>
