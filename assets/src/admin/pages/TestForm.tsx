import React from 'react';
import { useForm } from 'react-hook-form';
import {
  Card,
  Form,
  FormElement,
  InputCheckbox,
  InputRadio,
  InputSelect,
  InputText,
} from '../theme';

const TestForm = () => {
  const testForm = useForm<{
    input: string;
    checkbox: boolean;
    select: string;
    radio: string;
  }>({
    defaultValues: {
      input: 'string',
      checkbox: true,
      select: 'test',
      radio: 'test',
    },
  });

  const testValues = testForm.watch();
  console.log(testValues);

  return (
    <Card>
      <Form onSubmit={testForm.handleSubmit((data) => console.log(data))}>
        <FormElement
          form={testForm}
          label="Input"
          name="input"
          Input={InputText}
        />
        <FormElement
          form={testForm}
          label="Input"
          name="checkbox"
          Input={InputCheckbox}
        />
        <FormElement
          form={testForm}
          label="Input"
          name="select"
          Input={InputSelect}
          options={{ test1: 'Test1', test: 'Test' }}
        />
        <FormElement
          form={testForm}
          label="Input"
          name="radio"
          Input={InputRadio}
          options={{ test1: 'Test1', test: 'Test' }}
        />
      </Form>
    </Card>
  );
};

export default TestForm;
