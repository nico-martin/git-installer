import { ISettings, IPluginStrings, IMenuItems, IGitPackages } from './types';

declare global {
  interface Window {
    shguJsVars: {
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
      settingsParentKey: string;
      menu: IMenuItems;
      passwordProtectedDeactivated: number;
      passwordProtectedSkipTo: string;
      gitPackages: IGitPackages;
    };
  }
}

export const VARS = window.shguJsVars;
