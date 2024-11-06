# LASNTG Enrolment Log

Enrolment logs are to serve as the source of truth for all enrolments on the system vs the legacy implementation using post metadata.

Enrolment logs are stored in two db tables, `wp_posts` and `wp_enrolment_logs`.

`wp_posts` is used to store the enrolment status ( `publish` or `cancelled` ), created and modified date time, unique id etc.

`wp_enrolment_logs` links the post with it's corresponding order, product and attendee.

## Legacy Implementation

When an attendee is created or updated via the REST API then the order id is added to the attendees `order_ids` meta

When an attendee is created for private clients the order id is added to the attendees `order_ids` meta

When attendees are added to an order then the attendee ids are added to the order's `attendee_ids` meta.


```php
$attendee_meta = [
    'order_ids' => int[],
    'product_ids' => int[],
    'course_prerequisites_met' => serialize(int[])
];
```

## Order

```php
$order_meta = [
    'attendee_ids' => int[]
];


