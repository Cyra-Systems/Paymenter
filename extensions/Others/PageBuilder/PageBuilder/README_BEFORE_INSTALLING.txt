Hello, thank you for purchasing PageBuilder by Buzz Development

Installation:
- Drag and drop the PageBuilder extension folder (unzipped) into `/var/www/paymenter/extensions/Others` (or use the extension installer in Paymenter GUI)
- Run the command: `php artisan migrate --path=extensions/Others/PageBuilder/database/migrations`
- Run the command: `npm run build` or `npm run build <theme_name>` if you use a custom theme
- You will need to fill in the variables in the Extension settings such as colours, container size, box shadow CSS, transition settings, etc since they are empty
    - Or, you can quickly set them to the default values by running this command: `php artisan pagebuilder:reset-settings`

- All done! Head to Extensions in the admin panel to enable it, then click the "PageBuilder" sidebar link to add/remove/edit pages

If you need a good theme to go with PageBuilder, Luna is a great choice:
https://builtbybit.com/resources/luna-premium-paymenter-theme.73392/

We also have another theme called Cosmos which works really well with this extension:
https://builtbybit.com/resources/cosmos-modern-paymenter-theme.76370/

For support or feedback, please join our Discord: https://discord.gg/buzz and use the #pagebuilder-paymenter channel or #quick-support, or create a ticket.

We offer guaranteed response times quicker than 24 hours, but normally instantly.

Enjoy!

PS: If you use PageBuilder and would like to be featured on our resource page, please make a ticket in our Discord or DM ME! We'd love to feature your business and your use-case for PageBuilder!