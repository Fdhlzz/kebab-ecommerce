// 1. Get the user role from LocalStorage safely
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

// 2. Define Admin Menu Items (Reorganized)
const adminMenu = [
  // SECTION: OVERVIEW
  { 
    title: 'Dashboard', 
    to: { name: 'root' }, 
    icon: { icon: 'tabler-layout-dashboard' },
  },

  // SECTION: TRANSACTIONS (Most Frequent Actions)
  { heading: 'Penjualan' },
  { 
    title: 'Pesanan Masuk', 
    to: { name: 'orders' }, 
    icon: { icon: 'tabler-receipt' },
  },
  {
    title: 'Laporan',
    to: { name: 'laporan' }, 
    icon: { icon: 'tabler-report-money' },
  },

  // SECTION: MASTER DATA (Inventory & Menu)
  { heading: 'Katalog & Menu' },
  { 
    title: 'Daftar Menu', // Renamed from 'Produk'
    to: { name: 'inventory-products' }, 
    icon: { icon: 'tabler-chef-hat' }, // Changed to Chef Hat for food vibe
  },
  { 
    title: 'Kategori Menu', 
    to: { name: 'inventory-categories' }, 
    icon: { icon: 'tabler-category-2' },
  },

  // SECTION: LOGISTICS (Delivery)
  { heading: 'Logistik' },
  { 
    title: 'Data Kurir', 
    to: { name: 'couriers' }, 
    icon: { icon: 'tabler-users-group' },
  },
  { 
    title: 'Tarif & Wilayah', // Renamed from 'Ongkos Kirim'
    to: { name: 'shipping-rates' }, 
    icon: { icon: 'tabler-map-pins' },
  },

  // SECTION: SETTINGS
  { heading: 'Pengaturan' },
  {
    title: 'Metode Pembayaran', // Renamed from 'Upload QRIS'
    to: { name: 'settings-qris' }, 
    icon: { icon: 'tabler-qrcode' },
  },
]

// 3. Define Kurir Menu Items
const kurirMenu = [
  { 
    title: 'Tugas Pengantaran', // Renamed from 'Dashboard Kurir'
    to: { name: 'couriers-dashboard' }, 
    icon: { icon: 'tabler-moped' }, // Changed to Moped/Motorcycle
  },
]

// 4. Export based on role
export default role === 'kurir' ? kurirMenu : adminMenu
