import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { useSettingsForm, settingsKeys } from '../settings';
import {
  Button,
  Card,
  Form,
  FormControls,
  FormElement,
  FormFeedback,
  InputText,
  NOTICE_TYPES,
  PageContent,
  ShadowBox,
} from '../theme';
import { VARS } from '../utils/constants';
import { IGitPackages, ISettings } from '../utils/types';
import RepositoryListView from './GitPackages/RepositoryListView';
import AddRepository from './GitPackages/add/AddRepository';
import styles from './PageGitPackages.css';
import TestForm from './TestForm';

const PageGitPackages = () => {
  const [addPackageModal, setAddPackageModal] = React.useState<boolean>(false);
  const { form, submit, error, loading } = useSettingsForm(
    settingsKeys.filter((key) => key.indexOf('git-packages') === 0)
  );

  const [repositories, setRepositories] = React.useState<IGitPackages>(
    VARS.gitPackages
  );

  return (
    <PageContent>
      {/*<TestForm />*/}
      <Card
        title={__('Public Beta Info', 'shgi')}
        canToggleKey="public-beta-info"
      >
        <p style={{ fontSize: '1.2rem' }}>
          <b>Thank you so much for being a beta tester!</b>
        </p>
        <h3>Documentation</h3>
        <p>
          You can find a documentation of the most important features directly
          in the GitHub repository of the plugin
        </p>
        <p style={{ marginTop: '0.2rem' }}>
          <a
            href="https://github.com/SayHelloGmbH/git-installer"
            target="_blank"
            rel="noopener"
          >
            https://github.com/SayHelloGmbH/git-installer
          </a>
        </p>
        <h3>Issues</h3>
        <p>
          Please note that at this stage of development, errors can always
          occur. I would be very grateful if you could check if a bug has
          already been discovered or if you can create a new issue:
        </p>
        <p style={{ marginTop: '0.2rem' }}>
          <a
            href="https://github.com/SayHelloGmbH/git-installer/issues"
            target="_blank"
            rel="noreferrer"
          >
            https://github.com/SayHelloGmbH/git-installer/issues
          </a>
        </p>
        <h3>Contact</h3>
        <p>
          Furthermore, I would be happy to hear from you if you have any
          suggestions or ideas.
        </p>
        <p style={{ marginTop: '0.2rem' }}>
          <a href="mailto:nico@sayhello.ch">nico@sayhello.ch</a>
        </p>
      </Card>
      <Card
        title={__('Git Repositories', 'shgi')}
        rightContent={
          repositories.length !== 0 && (
            <Button
              buttonType="primary"
              onClick={() => setAddPackageModal(true)}
            >
              {__('add Repository', 'shgi')}
            </Button>
          )
        }
      >
        {repositories.length === 0 ? (
          <div className={styles.empty}>
            <p>{__('No repositories have been added yet.', 'shgi')}</p>
            <Button
              buttonType="primary"
              onClick={() => setAddPackageModal(true)}
            >
              {__('Add Repository', 'shgi')}
            </Button>
          </div>
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
      {addPackageModal && (
        <ShadowBox
          title={__('add Repository', 'shgi')}
          close={() => setAddPackageModal(false)}
          size="medium"
        >
          <AddRepository
            repositoryKeys={repositories.map((r) => r.key)}
            setRepositories={setRepositories}
            onFinish={() => setAddPackageModal(false)}
          />
        </ShadowBox>
      )}
      <Card title={__('Access control', 'shgi')} canToggleKey="git-packages">
        <Form onSubmit={submit}>
          <h3>GitHub</h3>
          <FormElement
            form={form}
            name="git-packages-github-token"
            Input={InputText}
            type="text"
            masked
            DescriptionInput={
              <React.Fragment>
                <p
                  dangerouslySetInnerHTML={{
                    __html: sprintf(
                      __(
                        'You can generate your personal acces token here: %s',
                        'shgi'
                      ),
                      '<a href="https://github.com/settings/tokens" target="_blank" rel="noreferrer">https://github.com/settings/tokens</a>'
                    ),
                  }}
                />
                <p
                  dangerouslySetInnerHTML={{
                    __html: sprintf(
                      __(
                        'The token must have access rights at least for the scope %s.',
                        'shgi'
                      ),
                      '<code>api</code>'
                    ),
                  }}
                />
              </React.Fragment>
            }
          />
          <h3>Gitlab</h3>
          <FormElement
            form={form}
            name="git-packages-gitlab-token"
            Input={InputText}
            type="text"
            masked
            DescriptionInput={
              <React.Fragment>
                <p
                  dangerouslySetInnerHTML={{
                    __html: sprintf(
                      __(
                        'You can generate your personal acces token here: %s',
                        'shgi'
                      ),
                      '<a href="https://gitlab.com/-/profile/personal_access_tokens" target="_blank" rel="noreferrer">https://gitlab.com/-/profile/personal_access_tokens</a>'
                    ),
                  }}
                />
                <p
                  dangerouslySetInnerHTML={{
                    __html: sprintf(
                      __(
                        'The token must have access rights at least for the scope %s.',
                        'shgi'
                      ),
                      '<code>read_api</code>'
                    ),
                  }}
                />
              </React.Fragment>
            }
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
            type="text"
            masked
            DescriptionInput={
              <React.Fragment>
                <p
                  dangerouslySetInnerHTML={{
                    __html: sprintf(
                      __('You can generate your app password here: %s', 'shgi'),
                      '<a href="https://bitbucket.org/account/settings/app-passwords/" target="_blank" rel="noreferrer">https://bitbucket.org/account/settings/app-passwords/</a>'
                    ),
                  }}
                />
                <p
                  dangerouslySetInnerHTML={{
                    __html: sprintf(
                      __(
                        'The app password must have at least the permission %s.',
                        'shgi'
                      ),
                      '<code>Repositorys: read</code>'
                    ),
                  }}
                />
              </React.Fragment>
            }
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
