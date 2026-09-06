# Featured Cards optional category filtering

Accepted 2026-09-06 for the portfolio Certifications page. This is an additive PageSchema 1.0 capability; existing cards and their required fields are unchanged.

`FeaturedCardsData.filtersEnabled?: boolean` enables category buttons. It defaults to false and the producer omits it when disabled. `FeaturedCard.categories?: string[]` contains category display labels. Categories are independent from tags; one card can belong to multiple categories. The producer strips markup, trims, deduplicates and discards empty or non-string values. The consumer validates the array and rejects malformed entries. Filtering uses exact category membership, never substring matching. All restores every card, including uncategorized cards.

`FeaturedCard.eyebrow?: string` supplies optional plain text above the title, such as a credential date. The existing Gutenberg widget exposes both new card fields and an Enable category filters switch. No new widget is registered.

Angular derives category buttons from the cards, sorted alphabetically, exposes `aria-pressed`, provides a live result count, and uses native keyboard-operable buttons. Initial server and client state both show all cards. Removing a selected category or disabling filters restores all cards.

Consumer-specific portfolio presentation remains in `tester/src/app/demo-page/demo-page.scss`.
