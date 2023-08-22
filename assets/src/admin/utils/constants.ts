import { ISettings, IPluginStrings, IMenuItems, IGitPackages } from './types';

declare global {
  interface Window {
    shgiJsVars: {
      ajaxUrl: string;
      homeUrl: string;
      adminUrl: string;
      generalError: string;
      pluginUrl: string;
      pluginPrefix: string;
      settings: ISettings;
      restBase: string;
      restPluginBase: string;
      restPluginNamespace: string;
      pluginStrings: IPluginStrings;
      nonce: string;
      multisite: boolean;
      settingsParentKey: string;
      menu: IMenuItems;
      passwordProtectedDeactivated: number;
      passwordProtectedSkipTo: string;
      gitPackages: IGitPackages;
      mustUsePlugins: boolean;
      activePlugins: Array<string>;
      postupdateHooks: Record<string, { title: string; description: string }>;
    };
  }
}

export const VARS = window.shgiJsVars;
