import { createAppConfig } from "@nextcloud/vite-config";
import { join, resolve } from "path";

export default createAppConfig(
  {
    "admin-settings": resolve(join("src", "admin-settings.js")),
  },
  {
    createEmptyCSSEntryPoints: true,
    extractLicenseInformation: true,
    thirdPartyLicense: false,
  }
);
