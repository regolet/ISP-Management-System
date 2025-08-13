const fs = require('fs');
const pool = require('./server/config/database');

async function insertMonitoringData() {
    try {
        console.log('Starting monitoring data insertion...');
        
        // Read JSON files
        const groupsData = JSON.parse(fs.readFileSync('./groups.json', 'utf8'));
        const categoriesData = JSON.parse(fs.readFileSync('./categories.json', 'utf8'));
        
        const client = await pool.connect();
        
        // Clear existing data
        console.log('Clearing existing monitoring data...');
        await client.query('DELETE FROM monitoring_categories');
        await client.query('DELETE FROM monitoring_groups');
        
        // Insert groups
        console.log('Inserting groups...');
        let groupCount = 0;
        
        for (const group of groupsData) {
            const query = `
                INSERT INTO monitoring_groups (
                    id, router_id, name, description, accounts, 
                    max_members, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            `;
            
            const values = [
                group.id,
                group.router_id,
                group.name,
                group.description || '',
                JSON.stringify(group.accounts || []),
                group.max_members || 8,
                group.created_at,
                group.updated_at || group.created_at
            ];
            
            await client.query(query, values);
            groupCount++;
            
            if (groupCount % 10 === 0) {
                console.log(`Inserted ${groupCount} groups...`);
            }
        }
        
        console.log(`✅ Inserted ${groupCount} groups`);
        
        // Insert categories
        console.log('Inserting categories...');
        let categoryCount = 0;
        
        for (const [routerId, categories] of Object.entries(categoriesData)) {
            for (let categoryIndex = 0; categoryIndex < categories.length; categoryIndex++) {
                const category = categories[categoryIndex];
                
                for (let subcategoryIndex = 0; subcategoryIndex < category.subcategories.length; subcategoryIndex++) {
                    const subcategory = category.subcategories[subcategoryIndex];
                    
                    const query = `
                        INSERT INTO monitoring_categories (
                            router_id, category, subcategory, group_ids,
                            category_index, subcategory_index
                        ) VALUES (?, ?, ?, ?, ?, ?)
                    `;
                    
                    const values = [
                        routerId,
                        category.category,
                        subcategory.subcategory,
                        JSON.stringify(subcategory.groups || []),
                        categoryIndex,
                        subcategoryIndex
                    ];
                    
                    await client.query(query, values);
                    categoryCount++;
                }
            }
        }
        
        console.log(`✅ Inserted ${categoryCount} categories`);
        
        // Verify data
        const groupsCount = await client.query('SELECT COUNT(*) as count FROM monitoring_groups');
        const categoriesCount = await client.query('SELECT COUNT(*) as count FROM monitoring_categories');
        
        console.log('\n📊 Final counts:');
        console.log(`Groups in database: ${groupsCount.count || groupsCount[0]?.count}`);
        console.log(`Categories in database: ${categoriesCount.count || categoriesCount[0]?.count}`);
        
        // Show some sample data
        const sampleGroups = await client.query('SELECT name, router_id, json_array_length(accounts) as account_count FROM monitoring_groups LIMIT 5');
        const sampleCategories = await client.query('SELECT category, subcategory, json_array_length(group_ids) as group_count FROM monitoring_categories LIMIT 5');
        
        console.log('\n📋 Sample groups:');
        if (Array.isArray(sampleGroups)) {
            sampleGroups.forEach(row => {
                console.log(`  - ${row.name} (${row.router_id}) - ${row.account_count || 0} accounts`);
            });
        }
        
        console.log('\n📋 Sample categories:');
        if (Array.isArray(sampleCategories)) {
            sampleCategories.forEach(row => {
                console.log(`  - ${row.category} > ${row.subcategory} - ${row.group_count || 0} groups`);
            });
        }
        
        client.release();
        console.log('\n🎉 Monitoring data insertion completed successfully!');
        
    } catch (error) {
        console.error('❌ Error inserting monitoring data:', error);
        process.exit(1);
    }
}

// Run the insertion
insertMonitoringData().then(() => {
    console.log('Script completed.');
    process.exit(0);
});