import React from 'react';
import { useForm } from 'react-hook-form';
import { UseFormReturn } from 'react-hook-form/dist/types';
import { apiGet, apiPost, pluginNamespace } from '../utils/apiFetch';
import { VARS } from '../utils/constants';
import {
  compareObjects,
  filterObject,
  keyValueFromSettings,
} from '../utils/objects';
import { ISettings, ISettingValue } from '../utils/types';

const event = new Event('settings');

const SettingsContext = React.createContext({
  settings: VARS.settings,
  savedSettings: VARS.settings,
  setSettings: (newSettingsValues: ISettings) => {},
  syncSettings: (keys: string[] = []) => new Promise((resolve, reject) => {}),
  loadSettings: () => new Promise((resolve, reject) => {}),
});

const postSettings = (data) => apiPost(pluginNamespace + 'settings', data);
const getSettings = () => apiGet(pluginNamespace + 'settings');

export const SettingsProvider = ({ children }: { children?: any }) => {
  const [settings, setSettings] = React.useState<ISettings>(VARS.settings);
  const [savedSettings, setSavedSettings] = React.useState<ISettings>(
    VARS.settings
  );

  return (
    <SettingsContext.Provider
      value={{
        settings,
        savedSettings,
        setSettings: (newSettingsValues: ISettings) => {
          const newSettings = {};
          Object.entries(newSettingsValues).map(([key, value]) => {
            newSettings[key] = {
              ...settings[key],
              value,
            };
          });
          setSettings({
            ...settings,
            ...newSettings,
          });
        },
        syncSettings: (keys: string[] = []) =>
          new Promise((resolve, reject) =>
            postSettings(keyValueFromSettings(filterObject(settings, keys)))
              .then((response: ISettings) => {
                resolve(response);
                setSavedSettings(response);
              })
              .catch((response) => {
                reject(response);
              })
          ),
        loadSettings: () =>
          new Promise((resolve, reject) =>
            getSettings()
              .then((response: ISettings) => {
                resolve(response);
                setSavedSettings(response);
              })
              .catch((response) => {
                reject(response);
              })
          ),
      }}
    >
      {children}
    </SettingsContext.Provider>
  );
};

export const useSettingsForm = (
  keys: string[] = []
): {
  form: UseFormReturn<Record<string, ISettingValue>>;
  submit: Function;
  error: string;
  loading: boolean;
  updateFieldValue: (key: string, value: any) => void;
  savedSettings: ISettings;
} => {
  const [loading, setLoading] = React.useState<boolean>(false);
  const [error, setError] = React.useState<string>('');

  const {
    settings,
    savedSettings,
    setSettings = () => {},
    syncSettings = () => Promise.resolve(),
  } = React.useContext(SettingsContext);

  const filteredSettings = React.useMemo<ISettings>(
    () => filterObject<ISettings>(settings, keys),
    [settings, keys]
  );

  const defaultValues = React.useMemo(
    () => keyValueFromSettings(filteredSettings),
    [filteredSettings]
  );

  const form = useForm<Record<string, ISettingValue>>({
    defaultValues,
  });

  const values: Record<string, any> = form.watch();
  React.useEffect(() => {
    if (!compareObjects(keyValueFromSettings(filteredSettings), values)) {
      setSettings(values);
    }
  }, [values]);

  const submit = form.handleSubmit((data) => {
    setLoading(true);
    setError('');
    syncSettings(keys)
      .then((data) => {
        setLoading(false);
      })
      .catch((e) => {
        setError(e);
        setLoading(false);
      });
  });

  const keyEvent = async (e: KeyboardEvent) => {
    if ((e.ctrlKey === true || e.metaKey === true) && e.key === 's') {
      e.preventDefault();
      await submit();
      return;
    }
  };

  React.useEffect(() => {
    window.addEventListener('keydown', keyEvent, false);
    return () => {
      window.removeEventListener('keydown', keyEvent);
    };
  }, [settings, savedSettings]);

  return {
    form,
    submit,
    error,
    loading,
    updateFieldValue: (key, value) => form.setValue(key, value),
    savedSettings,
  };
};

export const useSettingsDiff = (keys: string[] = []): boolean => {
  const { settings = {}, savedSettings = {} } =
    React.useContext(SettingsContext);

  return !compareObjects(
    filterObject(settings, keys),
    filterObject(savedSettings, keys)
  );
};

export const useSettings = (keys: string[] = []): ISettings => {
  const { savedSettings = {} } = React.useContext(SettingsContext);
  return filterObject<ISettings>(savedSettings, keys);
};

export const useTempSettings = (keys: string[] = []): ISettings => {
  const { settings = {} } = React.useContext(SettingsContext);
  return filterObject<ISettings>(settings, keys);
};

export const settingsKeys = Object.keys(VARS.settings);
