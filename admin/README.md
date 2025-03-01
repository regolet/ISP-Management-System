# FTTH Network Topology Management

This system manages Fiber-to-the-Home (FTTH) network topology, including OLTs, LCPs, and NAP boxes.

## Database Structure

### OLT Management
- `olt_devices`: Main OLT information
- `olt_ports`: Individual PON ports for each OLT
- `olt_splitter_types`: Available splitter configurations
- `olt_lcps`: Local Convergence Points
- `olt_naps`: Network Access Points

## Features

1. OLT Management
   - Add new OLTs with multiple PON ports
   - Track port availability
   - Support for GPON, EPON, and XGS-PON

2. LCP Management
   - Connect LCPs to OLT ports
   - Support for various splitter types
   - Track fiber distance and loss calculations

3. NAP Management
   - Connect NAPs to LCP ports
   - Track client connections
   - Monitor power budget

4. Power Budget Calculations
   - Automatic loss calculations including:
     - Splitter loss
     - Fiber distance loss (0.35 dB/km)
     - Safety margins

## CLI Tool Usage

```bash
# Initialize database with schema and test data
php cli/ftth_manage.php init

# List network components
php cli/ftth_manage.php list-olts
php cli/ftth_manage.php list-lcps
php cli/ftth_manage.php list-naps

# Check power budget for a NAP
php cli/ftth_manage.php check-power <nap_id>
```

## API Endpoints

### OLT Management
- `ftth_add_olt.php`: Add new OLT
- `ftth_get_olt_list.php`: List all OLTs

### LCP Management
- `ftth_add_lcp.php`: Add new LCP
- `ftth_get_lcp_list.php`: List all LCPs
- `ftth_get_lcp_details.php`: Get LCP details

### NAP Management
- `ftth_add_nap.php`: Add new NAP
- `ftth_get_nap_details.php`: Get NAP details

### Topology
- `ftth_get_network_topology.php`: Get complete network topology
- `ftth_get_splitter_types.php`: List available splitter types

## Web Interface

The web interface (`ftth_topology.php`) provides:
1. Interactive network topology visualization
2. Add/Edit/Delete functionality for OLTs, LCPs, and NAPs
3. Real-time power budget monitoring
4. Search and filter capabilities

## Power Budget Calculation

The system automatically calculates power budgets using:
```
Power Budget = TX Power - Receiver Sensitivity - Total Loss - Safety Margin

Where:
- Total Loss = Splitter Loss + Fiber Loss
- Fiber Loss = Distance (km) × 0.35 dB/km
- Safety Margin = 3 dB
- Receiver Sensitivity = -28 dBm (typical)
```

## Database Schema Updates

When updating the database schema:
1. Add new changes to `sql/ftth_schema.sql`
2. Update `sql/init_ftth_db.php` if needed
3. Run `php cli/ftth_manage.php init` to apply changes

## Development Notes

- All database operations use transactions for data integrity
- Power calculations follow ITU-T G.984 standards
- The system supports hierarchical connections (OLT → LCP → NAP)
- Each component tracks its port usage and availability
