# Product Recommendations System

## Overview
Smart recommendation system that suggests complementary products in the cart page based on what customers have already added.

---

## How It Works

### 1. **Intelligent Matching**
When items are in cart, the system:
- Analyzes product names and categories
- Matches against predefined product relationships
- Returns 6 most relevant complementary items

### 2. **Product Relationship Map**

#### Doors & Windows → Flashings
- `aluminium doors/windows` → head flashing, weatherboard, timber
- `windows` → head flashing, flashing
- `doors` → head flashing, flashing, hardware, handle

#### Roofing → Related
- `roofing iron` → gutter, downpipe, flashing, ridge cap
- `corrugated iron` → gutter, downpipe, flashing
- `roof tiles` → ridge cap, valley, flashing

#### Timber → Related
- `timber` → beam, joist, post, rail
- `weatherboard` → timber, flashing, corner

#### Kitchen → Related
- `kitchen` → sink, tap, bench, cupboard
- `benchtop` → sink, tap

#### Bathroom → Related
- `bathroom` → toilet, vanity, shower, basin
- `vanity` → basin, tap, mirror
- `shower` → tap, mixer, door

#### Hardware
- `handle` → lock, hinge, screw
- `lock` → handle, key, bolt

---

## API Endpoint

### `/backend/api/products/recommendations.php`

**Method:** `POST`

**Request:**
```json
{
  "product_ids": [123, 456, 789]
}
```

**Response:**
```json
{
  "success": true,
  "recommendations": [
    {
      "id": 101,
      "name": "Head Flashing - 3m",
      "price": "25.00",
      "stock_quantity": 15,
      "category_name": "Roofing Materials",
      "image": "uploads/products/flashing.jpg"
    }
  ],
  "debug": {
    "cart_products": 3,
    "keywords_found": ["head flashing", "flashing", "weatherboard"],
    "category_ids": [5, 8]
  }
}
```

---

## Frontend Implementation

### Display Section
Location: Below cart items, before footer

**Features:**
- Grid layout (responsive: 6 columns → 2 on mobile)
- Product cards with image, name, price
- One-click "Add to Cart" button
- Click card to view product details

### User Experience
1. User adds "Aluminium Windows" to cart
2. System shows: Head Flashing, Weatherboard, Timber products
3. User can quick-add or click to view details
4. Section auto-hides when cart is empty

---

## Customization

### Add New Product Relationships

Edit `/backend/api/products/recommendations.php`:

```php
$recommendations = [
    // Add your mappings
    'your_product_keyword' => ['related1', 'related2', 'related3'],
    
    // Example:
    'deck' => ['timber', 'post', 'rail', 'screw'],
    'fence' => ['post', 'wire', 'gate', 'latch'],
];
```

### Adjust Display Count

In recommendations.php, change LIMIT:
```php
LIMIT 6  // Change to 4, 8, 10, etc.
```

### Styling

In cart.php CSS section:
```css
.recommendations-grid {
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    /* Change minmax(180px, ...) to adjust card size */
}
```

---

## Testing Examples

### Test Case 1: Aluminium Doors
**Cart Contains:** "Aluminium Sliding Door"
**Expected Recommendations:**
- Head Flashing products
- Weatherboard
- Door handles/hardware

### Test Case 2: Roofing Iron
**Cart Contains:** "Corrugated Iron 0.4mm"
**Expected Recommendations:**
- Gutters
- Downpipes
- Ridge caps
- Flashings

### Test Case 3: Kitchen Benchtop
**Cart Contains:** "Laminate Benchtop"
**Expected Recommendations:**
- Kitchen sinks
- Tap fittings
- Cupboard handles

---

## Benefits

### For Customers
✅ Discover complementary products they might need
✅ Complete projects in one order
✅ Save time browsing

### For Business
✅ Increase average order value
✅ Reduce multiple orders/shipping
✅ Better customer satisfaction
✅ Showcase inventory

---

## Performance

- **Query Optimization:** Uses indexed columns (product_id, category_id)
- **Caching Ready:** Add Redis/Memcached for high traffic
- **Lazy Loading:** Recommendations load after cart items
- **Error Handling:** Gracefully hides section if API fails

---

## Future Enhancements

1. **Machine Learning:** Track "bought together" patterns
2. **Admin Interface:** Manage relationships via dashboard
3. **A/B Testing:** Test different recommendation strategies
4. **Analytics:** Track click-through and conversion rates
5. **Personalization:** Based on user purchase history

