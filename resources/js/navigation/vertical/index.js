// 1. Get the user role from LocalStorage
const getUserRole = () => {
  const userData = localStorage.getItem('userData')
  if (!userData) return null
  
  try {
    return JSON.parse(userData).role
  } catch (e) {
    return null
  }
}

const role = getUserRole()

// 2. Define Admin Menu Items
const adminMenu = [
  { 
    title: 'Dashboard', 
    to: { name: 'root' }, 
    icon: { icon: 'tabler-smart-home' },
  },
  
  { heading: 'Manajemen Toko' },
  { 
    title: 'Kategori', 
    to: { name: 'inventory-categories' }, 
    icon: { icon: 'tabler-category' },
  },
  { 
    title: 'Produk', 
    to: { name: 'inventory-products' }, 
    icon: { icon: 'tabler-box' },
  },
  { 
    title: 'Ongkos Kirim', 
    to: { name: 'shipping-rates' }, 
    icon: { icon: 'tabler-map-2' },
  },
  
  { heading: 'Operasional' },
  { 
    title: 'Manajemen Kurir', 
    to: { name: 'couriers' }, 
    icon: { icon: 'tabler-users' },
  },
  { 
    title: 'Pesanan Masuk', 
    to: { name: 'orders' }, 
    icon: { icon: 'tabler-clipboard-list' },
    badgeClass: 'bg-primary', 
  },
]

// 3. Define Kurir Menu Items
const kurirMenu = [
  { 
    title: 'Dashboard Kurir', 
    to: { name: 'couriers-dashboard' }, 
    icon: { icon: 'tabler-scooter' },
  },
]

// 4. Export the specific array based on role
// If role is 'kurir', show kurirMenu. Otherwise (admin), show adminMenu.
export default role === 'kurir' ? kurirMenu : adminMenu
