# SCIM Client

Use Nextcloud as an identity provider for external services
using the [SCIM] standard.

With this app,
you can sync Nextcloud users and groups to any number of SCIM servers
(specified in the administration settings).
Once an SCIM server is registered,
the app will start syncing all Nextcloud users and groups to that server.
Any changes in user or group information
(e.g. new or deleted users and groups, changes in personal information)
will also be monitored and pushed to all registered servers automatically.

<!--
## Usage

Install the app from the [App Store],
then head to **Identity Management** under the administration settings.
From there, click on **+ Register**
and fill in the SCIM server details in the form provided.

Once submitted and the server details have been validated,
that's it!
The app will automatically start performing a full sync for that server
as well as push any future changes to user/group information to that server.

To unregister an SCIM server,
simply click on **Delete** next to the desired server.
-->

## Development

To build the app from source,
clone the repository into your Nextcloud `apps` directory and run:

```sh
npm ci && npm run dev
```

Then, you can enable the app from the **Apps > Your apps** page
or by using the `occ` command:

```sh
./occ app:enable --force scim_client
```

<!-- TODO: uncomment once Andy has added the REUSE headers
## License

See each individual file for details, but in general,
this project is licensed under [AGPL-3.0-or-later].

This project adheres to the [REUSE Specification].
-->

<!-- Links -->

[SCIM]: https://scim.cloud/
<!-- [App Store]: https://apps.nextcloud.com/apps/scim_client -->
<!-- [AGPL-3.0-or-later]: ./LICENSES/AGPL-3.0-or-later.txt -->
<!-- [REUSE Specification]: https://reuse.software/spec/ -->
