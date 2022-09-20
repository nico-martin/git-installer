import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { useSettingsForm, settingsKeys } from '../settings';
import {
  Card,
  Form,
  FormControls,
  FormElement,
  FormFeedback,
  InputCheckbox,
  InputText,
  InputTextarea,
  Loader,
  NOTICE_TYPES,
  PageContent,
} from '../theme';
import { apiGet } from '../utils/apiFetch';
import { VARS } from '../utils/constants';
import dayjs from '../utils/dayjs';
import { IGitPackages, ISettings } from '../utils/types';
import AddRepositoryForm from './GitPackages/AddRepositoryForm';
import RepositoryListView from './GitPackages/RepositoryListView';
import styles from './PageGitPackages.css';

const PageGitPackages = () => {
  const { form, submit, error, loading, updateFieldValue, savedSettings } =
    useSettingsForm(
      settingsKeys.filter((key) => key.indexOf('git-packages') === 0)
    );
  const [repositories, setRepositories] = React.useState<IGitPackages>(
    VARS.gitPackages
  );

  return (
    <PageContent>
      <Card title={__('Git Repositories', 'shgu')}>
        {repositories.length === 0 ? (
          <p>{__('Es wurden noch keine Repositories gespeichert', 'shgu')}</p>
        ) : (
          repositories.map((repo, i) => (
            <RepositoryListView
              key={i}
              repository={repo}
              setRepositories={setRepositories}
              className={styles.repository}
            />
          ))
        )}
      </Card>
      <Card
        title={__('Repository hinzufügen', 'shgu')}
        canToggleKey="add-package"
      >
        <AddRepositoryForm setRepositories={setRepositories} />
      </Card>
      <Card title={__('Zugriffskontrolle', 'shgu')} canToggleKey="git-packages">
        <Form onSubmit={submit}>
          <h3>Gitlab</h3>
          <FormElement
            form={form}
            name="git-packages-gitlab-token"
            Input={InputText}
          />
          <h3>Github</h3>
          <FormElement
            form={form}
            name="git-packages-github-token"
            Input={InputText}
          />
          <h3>Bitbucket</h3>
          <FormElement
            form={form}
            name="git-packages-bitbucket-user"
            Input={InputText}
          />
          <FormElement
            form={form}
            name="git-packages-bitbucket-token"
            Input={InputText}
          />
          {error !== '' && (
            <FormFeedback type={NOTICE_TYPES.ERROR} message={error} />
          )}
          <FormControls type="submit" loading={loading} />
        </Form>
      </Card>
    </PageContent>
  );
};

export default PageGitPackages;
