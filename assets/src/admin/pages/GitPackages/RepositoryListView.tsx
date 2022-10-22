import { Message } from 'postcss';
import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { useToast } from '../../components/toast/toastContext';
import { Button, Icon, NOTICE_TYPES, Notice } from '../../theme';
import { apiDelete, apiGet } from '../../utils/apiFetch';
import cn from '../../utils/classnames';
import { VARS } from '../../utils/constants';
import { IGitPackage, IGitPackages } from '../../utils/types';
import styles from './RepositoryListView.css';
import DeleteRepository from './delete/DeleteRepository';

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
  const [loadingUpdate, setLoadingUpdate] = React.useState<boolean>(false);
  const updateUrl = `${VARS.restPluginBase}git-packages-deploy/${repository.key}/?key=${repository.deployKey}`;

  /*
  const deleteRepo = () => {
    setLoadingDelete(true);
    apiDelete<{
      message: string;
      packages: IGitPackages;
    }>(`${VARS.restPluginNamespace}/git-packages/${repository.key}`)
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
  };*/

  const updateRepo = () => {
    setLoadingUpdate(true);
    apiGet<IGitPackage>(
      `${VARS.restPluginNamespace}/git-packages-deploy/${repository.key}/?key=${repository.deployKey}`
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
            <p className={styles.version}>
              {sprintf(__('Version: %s', 'shgi'), repository.version)}
            </p>
            <p className={styles.repo}>{repository.baseUrl}</p>
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
            <p className={styles.pushToDeploy}>
              {__('Push to Deploy URL', 'shgi')}:
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
                        'Push to Deploy URL wurde in die Zwischenablage kopiert',
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
