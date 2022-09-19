export type ISettingValue = string | boolean;

export interface ISetting {
  value: any;
  label: string;
  values: Record<string, ISettingValue>;
}

export type ISettings = Record<string, ISetting>;

export type IPluginStrings = Record<string, string>;

export type IMenuItems = Record<
  string,
  {
    title: string;
    subtitle?: string;
    submenu?: Record<string, string>;
  }
>;

export interface IGitPackage {
  deployKey: string;
  name: string;
  theme: boolean;
  hoster: string;
  version: string;
  url: {
    api: string;
    repository: string;
    zip: string;
  };
}

export type IGitPackages = Array<IGitPackage>;
