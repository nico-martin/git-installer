import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { useToast } from '../../components/toast/toastContext';
import { Button, Icon, NOTICE_TYPES } from '../../theme';
import { apiDelete, apiGet } from '../../utils/apiFetch';
import cn from '../../utils/classnames';
import { VARS } from '../../utils/constants';
import { IGitPackage, IGitPackages } from '../../utils/types';
import styles from './RepositoryListView.css';

const RepositoryListView = ({
  repository,
  setRepositories,
  className = '',
}: {
  repository: IGitPackage;
  setRepositories: (packages: IGitPackages) => void;
  className?: string;
}) => {
  const { addToast } = useToast();
  const [loadingDelete, setLoadingDelete] = React.useState<boolean>(false);
  const [loadingUpdate, setLoadingUpdate] = React.useState<boolean>(false);
  const updateUrl = `${VARS.restPluginBase}git-packages-deploy/${repository.name}/?key=${repository.deployKey}`;
  const deleteRepo = () => {
    setLoadingDelete(true);
    apiDelete<{
      message: string;
      packages: IGitPackages;
    }>(`${VARS.restPluginNamespace}/git-packages/${repository.name}`)
      .then((resp) => {
        addToast({
          message: resp.message,
          type: NOTICE_TYPES.SUCCESS,
        });
        setRepositories(resp.packages);
      })
      .catch((e) =>
        addToast({
          message: e,
          type: NOTICE_TYPES.ERROR,
        })
      )
      .finally(() => {
        setLoadingDelete(false);
      });
  };

  const updateRepo = () => {
    setLoadingUpdate(true);
  };

  return (
    <div className={cn(className, styles.root)}>
      <div className={styles.infos}>
        <h3 className={styles.name}>
          <Icon icon={repository.hoster} className={styles.nameHoster} />
          {repository.theme ? __('Theme:', 'wpm-staging') + ' ' : ''}
          {repository.name}
        </h3>
        <p className={styles.version}>
          {sprintf(__('Version: %s', 'wpm-staging'), repository.version)}
        </p>
        <p className={styles.repo}>{repository.url.repository}</p>
        <p className={styles.pushToDeploy}>
          {__('Push to Deploy URL', 'wpm-staging')}:{' '}
          <input
            className={styles.pushToDeployInput}
            value={updateUrl}
            type="text"
            disabled
          />
          {Boolean(navigator.clipboard) && (
            <button
              className={styles.copyButton}
              onClick={() => {
                addToast({
                  message: __(
                    'Push to Deploy URL copied to clipboard',
                    'wpm-staging'
                  ),
                  type: NOTICE_TYPES.SUCCESS,
                });
                navigator.clipboard.writeText(updateUrl);
              }}
              title="Copy"
            >
              <Icon icon="copy" />
            </button>
          )}
        </p>
      </div>
      <div className={styles.controls}>
        <Button
          buttonType="primary"
          loading={loadingUpdate}
          onClick={updateRepo}
        >
          {__('Update', 'wpm-staging')}
        </Button>
        <Button
          buttonType="delete"
          loading={loadingDelete}
          onClick={deleteRepo}
        >
          {__('Delete', 'wpm-staging')}
        </Button>
      </div>
    </div>
  );
};

export default RepositoryListView;
