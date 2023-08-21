import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { useToast } from '../../components/toast/toastContext';
import { Button, Icon, NOTICE_TYPES, Notice } from '../../theme';
import { apiGet, apiPost } from '../../utils/apiFetch';
import cn from '../../utils/classnames';
import { VARS } from '../../utils/constants';
import { IGitPackage, IGitPackages } from '../../utils/types';
import styles from './RepositoryListView.css';
import DeleteRepository from './delete/DeleteRepository';
import RepositoryUpdateLog from './log/RepositoryUpdateLog';

const RepositoryListView = ({
  repository,
  setRepositories,
  className = '',
}: {
  repository: IGitPackage;
  setRepositories: (
    packages: IGitPackages | ((prevState: IGitPackages) => IGitPackages)
  ) => void;
  className?: string;
}) => {
  const { addToast } = useToast();
  const [deleteModal, setDeleteModal] = React.useState<boolean>(false);
  const [logModal, setLogModal] = React.useState<boolean>(false);
  const [loadingUpdate, setLoadingUpdate] = React.useState<boolean>(false);
  const [loadingHookUpdate, setLoadingHookUpdate] = React.useState<
    Array<string>
  >([]);
  const updateUrl = `${VARS.restPluginBase}git-packages-update/${repository.key}/?key=${repository.deployKey}&ref=webhook-update`;

  const updateRepo = () => {
    setLoadingUpdate(true);
    apiGet<IGitPackage>(
      `${VARS.restPluginNamespace}/git-packages-update/${repository.key}/?key=${repository.deployKey}&ref=update-trigger`
    )
      .then((resp) => {
        addToast({
          message: __('Update successful'),
          type: NOTICE_TYPES.SUCCESS,
        });
        setRepositories((packages) =>
          packages.map((p) => (p.key === resp.key ? resp : p))
        );
      })
      .catch((e) =>
        addToast({
          message: __('Update failed'),
          type: NOTICE_TYPES.ERROR,
        })
      )
      .finally(() => setLoadingUpdate(false));
  };

  const updateAfterUpdateHook = (key: string, checked: boolean) => {
    setLoadingHookUpdate((hooks) => [...hooks, key]);
    apiPost<IGitPackage>(
      `${VARS.restPluginNamespace}/hooks/after-update-hook/${repository.key}/`,
      { changedHooks: { [key]: checked } }
    )
      .then((resp) => {
        setRepositories((packages) =>
          packages.map((p) => (p.key === resp.key ? resp : p))
        );
      })
      .catch((e) =>
        addToast({
          message: __('Hook save failed'),
          type: NOTICE_TYPES.ERROR,
        })
      )
      .finally(() =>
        setLoadingHookUpdate((hooks) => hooks.filter((hook) => hook !== key))
      );
  };

  return (
    <div className={cn(className, styles.root)}>
      <div className={styles.infos}>
        <h3 className={styles.name}>
          <Icon icon={repository.provider} className={styles.nameHoster} />
          {repository.theme ? __('Theme:', 'shgi') + ' ' : ''}
          {repository.name}
          {repository.saveAsMustUsePlugin ? ' (MU)' : ''}
        </h3>
        {repository.version === null ? (
          <Notice className={styles.error} type={NOTICE_TYPES.ERROR}>
            {repository.theme
              ? __('The Theme does not seem to be installed', 'shgi')
              : __('The Plugin does not seem to be installed', 'shgi')}
          </Notice>
        ) : (
          <React.Fragment>
            <RepositoryUpdateLog
              repoKey={repository.key}
              name={repository.name}
              setModal={setLogModal}
              modal={logModal}
            />
            <p className={styles.version}>
              {sprintf(__('Version: %s', 'shgi'), repository.version)}
              <button
                className={styles.logButton}
                onClick={() => setLogModal(true)}
              >
                <Icon
                  icon="clipboard-text-clock-outline"
                  className={styles.logButtonIcon}
                />
              </button>
            </p>
            <p className={styles.repo}>
              {repository.baseUrl}{' '}
              <code className={styles.repoBranch}>
                <Icon icon="source-branch" className={styles.repoBranchIcon} />{' '}
                {repository.activeBranch}
              </code>
            </p>
            {repository.dir && (
              <p
                dangerouslySetInnerHTML={{
                  __html: sprintf(
                    __('Directory: %s', 'shgi'),
                    `<code>./${repository.dir}</code>`
                  ),
                }}
              />
            )}
            <p className={styles.webhookUpdate}>
              {__('Webhook Update URL', 'shgi')}:
              <input
                className={styles.webhookUpdateInput}
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
                        'Webhook Update URL was copied to the clipboard',
                        'shgi'
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
            <p className={styles.afterUpdateHook}>
              <b>{__('After Update Hooks', 'shgi')}:</b>
              {Object.entries(VARS.afterUpdateHooks).map(([key, title]) => (
                <span key={key}>
                  <input
                    id={`${repository.key}-update-after-update-hook-${key}`}
                    type="checkbox"
                    onChange={(e) =>
                      updateAfterUpdateHook(key, e.target.checked)
                    }
                    defaultChecked={
                      (repository.afterUpdateHooks || []).indexOf(key) !== -1
                    }
                    disabled={loadingHookUpdate.indexOf(key) !== -1}
                  />
                  <label
                    htmlFor={`${repository.key}-update-after-update-hook-${key}`}
                  >
                    {' '}
                    {title}
                  </label>
                </span>
              ))}
            </p>
          </React.Fragment>
        )}
      </div>
      <div className={styles.controls}>
        <DeleteRepository
          modal={deleteModal}
          setModal={setDeleteModal}
          repositoryKey={repository.key}
          theme={repository.theme}
          name={repository.name}
          setRepositories={setRepositories}
        />
        <Button
          buttonType="primary"
          loading={loadingUpdate}
          onClick={updateRepo}
        >
          {__('Update', 'shgi')}
        </Button>
        <Button buttonType="delete" onClick={() => setDeleteModal(true)}>
          {__('Delete', 'shgi')}
        </Button>
      </div>
    </div>
  );
};

export default RepositoryListView;
