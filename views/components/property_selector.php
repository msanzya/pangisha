<?php
/**
 * Property Selector Component
 * A dropdown selector for users to switch between properties they're related to
 */

// This component requires the PropertyRelationship model
require_once __DIR__.'/../../models/PropertyRelationship.php';

// Initialize property relationship model
$propertyRelationship = new PropertyRelationship($db);

// Get user's properties
$userProperties = $propertyRelationship->getUserProperties($_SESSION['user_id']);

// Get current active property from session or default to first property
$activePropertyId = $_SESSION['active_property_id'] ?? ($userProperties[0]['property_id'] ?? null);
?>

<div class="property-selector-container">
    <div class="property-selector">
        <label for="propertySelect" class="property-selector-label">
            <i class="bi bi-house-door"></i> Active Property:
        </label>
        <select id="propertySelect" class="form-select property-selector-dropdown" onchange="changeActiveProperty(this.value)">
            <?php if (empty($userProperties)): ?>
                <option value="">No properties available</option>
            <?php else: ?>
                <?php foreach ($userProperties as $property): ?>
                    <option value="<?= $property['property_id'] ?>" <?= ($property['property_id'] == $activePropertyId) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($property['property_title']) ?> 
                        (<?= ucfirst(str_replace('_', ' ', $property['relationship_type'])) ?>)
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>
</div>

<script>
function changeActiveProperty(propertyId) {
    // In a real implementation, this would make an AJAX call to update the session
    // For now, we'll just reload the page with the property ID as a parameter
    const url = new URL(window.location);
    url.searchParams.set('property_id', propertyId);
    window.location.href = url.toString();
    
    // Alternative AJAX implementation:
    /*
    fetch('<?= BASE_URL ?>api/set_active_property.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ property_id: propertyId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the page or update the UI dynamically
            location.reload();
        } else {
            console.error('Failed to set active property');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
    */
}
</script>

<style>
.property-selector-container {
    margin-bottom: 1rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border);
}

.property-selector {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.property-selector-label {
    font-weight: 500;
    color: var(--dark);
    margin: 0;
}

.property-selector-dropdown {
    max-width: 300px;
    border: 1px solid var(--border);
    border-radius: 4px;
}
</style>