// Sidebar navigation helper
function getSidebarHTML(currentPage) {
  const pages = [
    { href: '/dashboard.html', icon: 'fa-tachometer-alt', text: 'Dashboard', id: 'dashboard' },
    { href: '/clients.html', icon: 'fa-users', text: 'Clients', id: 'clients' },
    { href: '/plans.html', icon: 'fa-project-diagram', text: 'Plans', id: 'plans' },
    { href: '/billings.html', icon: 'fa-file-invoice-dollar', text: 'Billings', id: 'billings' },
    { href: '/payments.html', icon: 'fa-credit-card', text: 'Payments', id: 'payments' },
    { href: '/inventory.html', icon: 'fa-boxes', text: 'Inventory', id: 'inventory' },
    { href: '/tickets.html', icon: 'fa-ticket-alt', text: 'Tickets', id: 'tickets' },
    { href: '/monitoring.html', icon: 'fa-chart-line', text: 'Monitoring', id: 'monitoring' },
    { href: '/settings.html', icon: 'fa-cogs', text: 'Settings', id: 'settings' }
  ];

  return pages.map(page => `
    <a href="${page.href}" class="flex items-center space-x-3 px-4 py-3 rounded-lg ${
      currentPage === page.id ? 'bg-white/20 text-white font-medium' : 'hover:bg-white/10 transition-colors text-white/90 hover:text-white'
    }">
      <i class="fas ${page.icon} w-5"></i>
      <span>${page.text}</span>
    </a>
  `).join('');
}