-- Add new translations for order statuses and methods if they don't exist
INSERT IGNORE INTO `translations` (`language_code`, `translation_key`, `translation_value`, `context`, `created_at`, `updated_at`) VALUES
-- Order Status Translations
('en', 'admin.orders.status.pending', 'Pending', 'admin', NOW(), NOW()),
('hu', 'admin.orders.status.pending', 'Függőben', 'admin', NOW(), NOW()),
('en', 'admin.orders.status.processing', 'Processing', 'admin', NOW(), NOW()),
('hu', 'admin.orders.status.processing', 'Feldolgozás alatt', 'admin', NOW(), NOW()),
('en', 'admin.orders.status.shipped', 'Shipped', 'admin', NOW(), NOW()),
('hu', 'admin.orders.status.shipped', 'Kiszállítva', 'admin', NOW(), NOW()),
('en', 'admin.orders.status.delivered', 'Delivered', 'admin', NOW(), NOW()),
('hu', 'admin.orders.status.delivered', 'Kézbesítve', 'admin', NOW(), NOW()),
('en', 'admin.orders.status.cancelled', 'Cancelled', 'admin', NOW(), NOW()),
('hu', 'admin.orders.status.cancelled', 'Törölve', 'admin', NOW(), NOW()),

-- Payment Method Translations
('en', 'admin.orders.payment.card', 'Card', 'admin', NOW(), NOW()),
('hu', 'admin.orders.payment.card', 'Bankkártya', 'admin', NOW(), NOW()),
('en', 'admin.orders.payment.transfer', 'Transfer', 'admin', NOW(), NOW()),
('hu', 'admin.orders.payment.transfer', 'Átutalás', 'admin', NOW(), NOW()),
('en', 'admin.orders.payment.cash_on_delivery', 'COD', 'admin', NOW(), NOW()),
('hu', 'admin.orders.payment.cash_on_delivery', 'Utánvét', 'admin', NOW(), NOW()),

-- Payment Status Translations
('en', 'admin.orders.payment_status.paid', 'Paid', 'admin', NOW(), NOW()),
('hu', 'admin.orders.payment_status.paid', 'Fizetve', 'admin', NOW(), NOW()),
('en', 'admin.orders.payment_status.pending_payment', 'Pending Payment', 'admin', NOW(), NOW()),
('hu', 'admin.orders.payment_status.pending_payment', 'Fizetésre vár', 'admin', NOW(), NOW()),
('en', 'admin.orders.payment_status.cash_on_delivery', 'COD', 'admin', NOW(), NOW()),
('hu', 'admin.orders.payment_status.cash_on_delivery', 'Utánvét', 'admin', NOW(), NOW()),

-- Shipping Method Translations
('en', 'admin.orders.shipping.personal', 'Personal Pickup', 'admin', NOW(), NOW()),
('hu', 'admin.orders.shipping.personal', 'Személyes átvétel', 'admin', NOW(), NOW()),
('en', 'admin.orders.shipping.gls', 'GLS Delivery', 'admin', NOW(), NOW()),
('hu', 'admin.orders.shipping.gls', 'GLS futár', 'admin', NOW(), NOW()),
('en', 'admin.orders.shipping.dpd', 'DPD', 'admin', NOW(), NOW()),
('hu', 'admin.orders.shipping.dpd', 'DPD', 'admin', NOW(), NOW()),
('en', 'admin.orders.shipping.mpl', 'MPL Delivery', 'admin', NOW(), NOW()),
('hu', 'admin.orders.shipping.mpl', 'MPL futár', 'admin', NOW(), NOW()),
('en', 'admin.orders.shipping.automat', 'Parcel Locker', 'admin', NOW(), NOW()),
('hu', 'admin.orders.shipping.automat', 'Csomagautomata', 'admin', NOW(), NOW()),

-- Table Headers
('en', 'admin.orders.table.order_number', 'Order Number', 'admin', NOW(), NOW()),
('hu', 'admin.orders.table.order_number', 'Rendelési szám', 'admin', NOW(), NOW()),
('en', 'admin.orders.table.customer', 'Customer', 'admin', NOW(), NOW()),
('hu', 'admin.orders.table.customer', 'Vásárló', 'admin', NOW(), NOW()),
('en', 'admin.orders.table.shipping_method', 'Shipping Method', 'admin', NOW(), NOW()),
('hu', 'admin.orders.table.shipping_method', 'Szállítási mód', 'admin', NOW(), NOW()),
('en', 'admin.orders.table.payment_method', 'Payment Method', 'admin', NOW(), NOW()),
('hu', 'admin.orders.table.payment_method', 'Fizetési mód', 'admin', NOW(), NOW()),
('en', 'admin.orders.table.payment_status', 'Payment Status', 'admin', NOW(), NOW()),
('hu', 'admin.orders.table.payment_status', 'Fizetési státusz', 'admin', NOW(), NOW()),
('en', 'admin.orders.table.order_status', 'Order Status', 'admin', NOW(), NOW()),
('hu', 'admin.orders.table.order_status', 'Rendelés státusza', 'admin', NOW(), NOW()),
('en', 'admin.orders.table.total', 'Total', 'admin', NOW(), NOW()),
('hu', 'admin.orders.table.total', 'Összeg', 'admin', NOW(), NOW()),
('en', 'admin.orders.table.date', 'Date', 'admin', NOW(), NOW()),
('hu', 'admin.orders.table.date', 'Dátum', 'admin', NOW(), NOW()),
('en', 'admin.orders.table.actions', 'Actions', 'admin', NOW(), NOW()),
('hu', 'admin.orders.table.actions', 'Műveletek', 'admin', NOW(), NOW()),

-- Buttons and Other UI Elements
('en', 'admin.orders.button.details', 'Details', 'admin', NOW(), NOW()),
('hu', 'admin.orders.button.details', 'Részletek', 'admin', NOW(), NOW()),
('en', 'admin.orders.modal.title', 'Order Details', 'admin', NOW(), NOW()),
('hu', 'admin.orders.modal.title', 'Rendelés részletei', 'admin', NOW(), NOW());
