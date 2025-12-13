export default [
  {
    title: 'dashboard',
    to: { name: 'root' },
    icon: { icon: 'tabler-smart-home' },
  },
  { heading: 'Manajemen Toko' },
  {
    title: 'Pesanan',
    to: { name: 'orders' },
    icon: { icon: 'tabler-shopping-cart' },
  },
  {
    title: 'Inventaris',
    icon: { icon: 'tabler-box' },
    children: [
      { title: 'Kategori', to: { name: 'inventory-categories' } },
      { title: 'Produk Menu', to: { name: 'inventory-products' } },
    ],
  },
  {
    title: 'Pengaturan Ongkir',
    to: { name: 'shipping-rates' },
    icon: { icon: 'tabler-truck' },
  },
  {
    title: 'Manajemen Kurir',
    to: { name: 'couriers' },
    icon: { icon: 'tabler-users' },
  },
  
]
