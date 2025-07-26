// Helper function to handle date formatting for PostgreSQL
function formatDateForDB(dateString) {
  if (!dateString) return null;
  // Return the date string as-is to avoid timezone conversion
  return dateString;
}

// Helper function to format dates in responses
function formatClientDates(client) {
  if (!client) return client;
  
  if (client.installation_date && typeof client.installation_date === 'string') {
    if (client.installation_date.includes('T')) {
      client.installation_date = client.installation_date.split('T')[0];
    }
  }
  if (client.due_date && typeof client.due_date === 'string') {
    if (client.due_date.includes('T')) {
      client.due_date = client.due_date.split('T')[0];
    }
  }
  return client;
}

module.exports = {
  formatDateForDB,
  formatClientDates
};