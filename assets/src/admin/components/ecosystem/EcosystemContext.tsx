import React from 'react';
import { __ } from '@wordpress/i18n';
import { NOTICE_TYPES } from '../../theme';
import { pluginNamespace } from '../../utils/apiFetch';
import { VARS } from '../../utils/constants';

interface ContextI {
  notices: Array<{ type: NOTICE_TYPES; message: string }>;
}

const Context = React.createContext<ContextI>({ notices: [] });

export const EcosystemProvider = ({ children }: { children: any }) => {
  const [showRestWarning, setShowRestWarning] = React.useState<boolean>(null);

  React.useEffect(() => {
    fetch(VARS.restBase + pluginNamespace + 'ping', {
      credentials: 'omit',
    })
      .then((resp) => setShowRestWarning(resp.status > 300))
      .catch(() => setShowRestWarning(true));
  }, []);

  const restWarning: string = React.useMemo(() => {
    if (!showRestWarning) return '';
    return VARS.activePlugins.indexOf(
      'password-protected/password-protected.php'
    ) !== -1
      ? __(
          'The Plugin "Password Protected" is active and enabled. Therefore the REST API Access is restricted and features like the "webhook update URL" won\'t work. Please allow REST API Access under "Protected Permissions".',
          'shgi'
        ) +
          `<br /><a href="${VARS.adminUrl}options-general.php?page=password-protected">${VARS.adminUrl}options-general.php?page=password-protected</a>`
      : __(
          'REST API Access seems to be restricted. Therefore features like the "webhook update URL" won\'t work. Please make sure that the REST API can be accessed for visitors.',
          'shgi'
        );
  }, [showRestWarning]);

  return (
    <Context.Provider
      value={{
        notices: [
          ...(restWarning
            ? [{ type: NOTICE_TYPES.ERROR, message: restWarning }]
            : []),
        ],
      }}
    >
      {children}
    </Context.Provider>
  );
};

export const useEcosystemNotices = () => {
  const { notices } = React.useContext(Context);
  return notices;
};
